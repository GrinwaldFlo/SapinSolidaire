<?php

namespace App\Livewire\Admin;

use App\Livewire\Admin\Concerns\InsertsCommentTemplates;
use App\Livewire\Admin\Concerns\SendsRejectionEmail;
use App\Models\AdminActionLog;
use App\Models\Child;
use App\Models\GiftRequest;
use App\Models\Season;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Gift (children) validation. Only requests of families already validated on
 * the FamilyValidation page are listed here; the family itself is not decided.
 */
class Validation extends Component
{
    use InsertsCommentTemplates, SendsRejectionEmail;

    private const LOCK_TTL_SECONDS = 300;

    public ?Season $activeSeason = null;

    public ?GiftRequest $currentRequest = null;

    public int $queuedFamiliesCount = 0;

    public int $pendingChildrenCount = 0;

    // Form state
    public array $childDecisions = []; // child_id => 'pending', 'validated', 'correction', 'rejected'

    public array $childComments = []; // child_id => text

    public array $predefinedRejectionMessages = []; // index => message

    public array $selectedChildMessageKeys = []; // child_id => message index

    /**
     * Children may be left pending (e.g. a correction asked for one child only), but at least one must be decided.
     */
    public function getHasNoDecisionProperty(): bool
    {
        return array_diff($this->childDecisions, ['pending']) === [];
    }

    public function mount(): void
    {
        $this->activeSeason = Season::getActive();
        $this->predefinedRejectionMessages = Setting::getValidationCommentTemplates();
        $this->loadNextRequest();
        $this->loadCounts();
    }

    /**
     * Validated requests of the active season that still have children to decide.
     */
    protected function queueQuery(): Builder
    {
        return GiftRequest::where('season_id', $this->activeSeason->id)
            ->where('status', GiftRequest::STATUS_VALIDATED)
            ->whereHas('children', function ($q) {
                $q->where('status', Child::STATUS_PENDING);
            });
    }

    protected function loadNextRequest(): void
    {
        if (! $this->activeSeason) {
            $this->currentRequest = null;

            return;
        }

        $adminId = (string) auth()->id();
        $this->releaseReservation($adminId);

        $candidateIds = $this->queueQuery()
            ->orderBy('updated_at')
            ->pluck('id');

        $this->currentRequest = null;

        foreach ($candidateIds as $candidateId) {
            $lockKey = "validation_lock:{$candidateId}";

            // Try to atomically acquire the lock, or reuse our own
            if (Cache::add($lockKey, $adminId, self::LOCK_TTL_SECONDS)
                || Cache::get($lockKey) === $adminId) {
                Cache::put("validation_admin:{$adminId}", $candidateId, self::LOCK_TTL_SECONDS);
                $this->currentRequest = GiftRequest::with(['family', 'children'])->find($candidateId);
                $this->initFormState();
                break;
            }
        }
    }

    protected function initFormState(): void
    {
        $this->childDecisions = [];
        $this->childComments = [];
        $this->selectedChildMessageKeys = [];

        foreach ($this->currentRequest->children as $child) {
            if ($child->status !== Child::STATUS_PENDING) {
                continue;
            }

            $this->childDecisions[$child->id] = 'pending';
            $this->childComments[$child->id] = '';
            $this->selectedChildMessageKeys[$child->id] = '';
        }
    }

    public function applyChildMessage(string $childId): void
    {
        $messageKey = $this->selectedChildMessageKeys[$childId] ?? '';
        $message = $this->resolveTemplateMessage((string) $messageKey);

        if ($message === '') {
            return;
        }

        $currentComment = $this->childComments[$childId] ?? '';
        $this->childComments[$childId] = $this->appendTemplateMessage($currentComment, $message);
        $this->resetErrorBag("childComments.$childId");
    }

    protected function releaseReservation(string $adminId): void
    {
        $previousRequestId = Cache::get("validation_admin:{$adminId}");

        if ($previousRequestId) {
            Cache::forget("validation_lock:{$previousRequestId}");
            Cache::forget("validation_admin:{$adminId}");
        }
    }

    protected function loadCounts(): void
    {
        if (! $this->activeSeason) {
            return;
        }

        $this->queuedFamiliesCount = $this->queueQuery()->count();

        $this->pendingChildrenCount = Child::whereHas('giftRequest', function ($q) {
            $q->where('season_id', $this->activeSeason->id)
                ->where('status', GiftRequest::STATUS_VALIDATED);
        })->where('status', Child::STATUS_PENDING)->count();
    }

    public function submitValidation(): void
    {
        if (! $this->currentRequest || $this->has_no_decision) {
            return;
        }

        $this->validate([
            'childDecisions.*' => 'required|in:pending,validated,correction,rejected',
            'childComments.*' => 'required_if:childDecisions.*,correction,rejected',
        ], [
            'childComments.*.required_if' => 'Le commentaire est obligatoire pour un refus ou une demande de correction.',
        ]);

        $combinedComments = [];
        $validatedChildren = [];

        $saved = DB::transaction(function () use (&$combinedComments, &$validatedChildren) {
            $request = GiftRequest::lockForUpdate()->find($this->currentRequest->id);

            // The family may have changed its request (back to pending) in the meantime
            if (! $request || $request->status !== GiftRequest::STATUS_VALIDATED) {
                return false;
            }

            foreach ($this->currentRequest->children as $childModel) {
                $child = Child::lockForUpdate()->find($childModel->id);

                // Only children still waiting for a decision are handled here
                if (! $child || $child->status !== Child::STATUS_PENDING) {
                    continue;
                }

                $decision = $this->childDecisions[$child->id] ?? 'pending';
                $comment = $this->childComments[$child->id] ?? '';

                if ($decision === 'validated') {
                    if (! $child->code) {
                        $child->assignChildNumberAndCode();
                    }
                    $child->setStatus(Child::STATUS_VALIDATED);
                    $validatedChildren[] = $child->fresh();
                } elseif ($decision === 'correction') {
                    $child->setStatus(Child::STATUS_REJECTED, $comment);
                    $combinedComments[] = "Pour l'enfant {$child->first_name} :\n - ".$comment;
                } elseif ($decision === 'rejected') {
                    $child->setStatus(Child::STATUS_REJECTED_FINAL, $comment);
                    $combinedComments[] = "Refus pour l'enfant {$child->first_name} :\n - ".$comment;
                }
            }

            // Children left pending: the family goes to the end of the queue
            $request->touch();

            return true;
        });

        if (! $saved) {
            session()->flash('error', "La famille a modifié sa demande pendant la validation : vos décisions n'ont pas été enregistrées. La demande doit d'abord être revalidée sur la page Validation des familles.");
            $this->loadNextRequest();
            $this->loadCounts();

            return;
        }

        $this->logValidatedChildren($validatedChildren);

        if (! empty($combinedComments)) {
            $finalComment = implode("\n-------------------\n", $combinedComments);
            // The family stays validated: it receives a correction email (with access link), never a final rejection
            $this->sendRejectionEmail($this->currentRequest, false, $finalComment);
        }

        $this->loadNextRequest();
        $this->loadCounts();

        $this->dispatch('scroll-to-top');
    }

    /**
     * @param  array<int, Child>  $children
     */
    protected function logValidatedChildren(array $children): void
    {
        $family = $this->currentRequest?->family;
        $familyName = trim((string) $family?->full_name) !== ''
            ? $family->full_name
            : ($family?->email ?? 'Famille inconnue');

        foreach ($children as $child) {
            AdminActionLog::record(
                AdminActionLog::ACTION_CHILD_VALIDATED,
                "Validation enfant: {$child->first_name} (famille: {$familyName})",
                $this->currentRequest?->family_id,
                $child->gift_request_id,
                $child->id,
            );
        }
    }

    public function skip(): void
    {
        if ($this->currentRequest) {
            $this->currentRequest->touch();
            $this->releaseReservation((string) auth()->id());
        }

        $this->loadNextRequest();
        $this->loadCounts();

        $this->dispatch('scroll-to-top');
    }

    public function render()
    {
        return view('livewire.admin.validation');
    }
}

<?php

namespace App\Livewire\Admin;

use App\Livewire\Admin\Concerns\HandlesFamilyValidation;
use App\Models\Child;
use App\Models\GiftRequest;
use App\Models\Season;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Validation extends Component
{
    use HandlesFamilyValidation;

    private const LOCK_TTL_SECONDS = 300;

    public ?Season $activeSeason = null;
    public ?GiftRequest $currentRequest = null;
    public int $pendingFamiliesCount = 0;
    public int $pendingChildrenCount = 0;

    public string $rejectionType = ''; // 'family', 'child'

    // Form state
    public string $familyDecision = 'pending'; // 'pending', 'validated', 'correction', 'rejected'
    public string $familyComment = '';
    public array $childDecisions = []; // child_id => 'pending', 'validated', 'correction', 'rejected'
    public array $childComments = []; // child_id => text

    public function getHasPendingDecisionsProperty(): bool
    {
        if ($this->currentRequest && $this->currentRequest->status === GiftRequest::STATUS_PENDING && $this->familyDecision === 'pending') {
            return true;
        }

        foreach ($this->childDecisions as $decision) {
            if ($decision === 'pending') {
                return true;
            }
        }

        return false;
    }

    public function mount(): void
    {
        $this->activeSeason = Season::getActive();
        $this->loadNextRequest();
        $this->loadCounts();
    }

    protected function loadNextRequest(): void
    {
        if (! $this->activeSeason) {
            $this->currentRequest = null;

            return;
        }

        $adminId = (string) auth()->id();
        $this->releaseReservation($adminId);

        // Get IDs of pending requests in queue order
        $candidateIds = GiftRequest::where('season_id', $this->activeSeason->id)
            ->where(function ($query) {
                $query->where('status', GiftRequest::STATUS_PENDING)
                    ->orWhereHas('children', function ($q) {
                        $q->where('status', Child::STATUS_PENDING);
                    });
            })
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
        $this->familyDecision = $this->currentRequest->status === GiftRequest::STATUS_PENDING ? 'pending' : 'validated';
        $this->familyComment = '';
        $this->childDecisions = [];
        $this->childComments = [];

        foreach ($this->currentRequest->children as $child) {
            $this->childDecisions[$child->id] = $child->status === Child::STATUS_PENDING ? 'pending' : 'validated';
            $this->childComments[$child->id] = '';
        }
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

        $this->pendingFamiliesCount = GiftRequest::where('season_id', $this->activeSeason->id)
            ->where('status', GiftRequest::STATUS_PENDING)
            ->count();

        $this->pendingChildrenCount = Child::whereHas('giftRequest', function ($q) {
            $q->where('season_id', $this->activeSeason->id);
        })->where('status', Child::STATUS_PENDING)->count();
    }

    public function submitValidation(): void
    {
        $this->validate([
            'familyDecision' => 'required|in:pending,validated,correction,rejected',
            'familyComment' => 'required_if:familyDecision,correction,rejected',
            'childDecisions.*' => 'required|in:pending,validated,correction,rejected',
            'childComments.*' => 'required_if:childDecisions.*,correction,rejected',
        ], [
            'familyComment.required_if' => 'Le commentaire est obligatoire pour un refus ou une demande de correction.',
            'childComments.*.required_if' => 'Le commentaire est obligatoire pour un refus ou une demande de correction.',
        ]);

        $hasCorrection = $this->familyDecision === 'correction';
        $hasRejection = $this->familyDecision === 'rejected';
        
        $combinedComments = [];
        
        DB::transaction(function () use (&$hasCorrection, &$hasRejection, &$combinedComments) {
            $request = GiftRequest::lockForUpdate()->find($this->currentRequest->id);
            
            if ($this->familyDecision === 'validated') {
                if ($request->family_number === null) {
                    $request->family_number = $this->activeSeason->assignNextFamilyNumber();
                    $request->save();
                }
                $request->setStatus(GiftRequest::STATUS_VALIDATED);
            } elseif ($this->familyDecision === 'correction') {
                $request->setStatus(GiftRequest::STATUS_REJECTED, $this->familyComment);
                $combinedComments[] = "Concernant la famille :\n" . $this->familyComment;
            } elseif ($this->familyDecision === 'rejected') {
                $request->setStatus(GiftRequest::STATUS_REJECTED_FINAL, $this->familyComment);
                $combinedComments[] = "Refus définitif de la famille :\n" . $this->familyComment;
            }

            foreach ($this->currentRequest->children as $childModel) {
                $child = Child::lockForUpdate()->find($childModel->id);
                $decision = $this->childDecisions[$child->id] ?? 'pending';
                $comment = $this->childComments[$child->id] ?? '';

                if ($decision === 'validated') {
                    if (! $child->code) {
                        $child->assignChildNumberAndCode();
                    }
                    $child->setStatus(Child::STATUS_VALIDATED);
                } elseif ($decision === 'correction') {
                    $hasCorrection = true;
                    $child->setStatus(Child::STATUS_REJECTED, $comment);
                    $combinedComments[] = "Pour l'enfant {$child->first_name} :\n" . $comment;
                } elseif ($decision === 'rejected') {
                    $hasRejection = true;
                    $child->setStatus(Child::STATUS_REJECTED_FINAL, $comment);
                    $combinedComments[] = "Refus pour l'enfant {$child->first_name} :\n" . $comment;
                }
            }
        });

        if (!empty($combinedComments)) {
            $finalComment = implode("\n\n-------------------\n\n", $combinedComments);
            // If the family is completely rejected, we considered it a final rejection
            $isFinal = ($this->familyDecision === 'rejected'); 
            
            $this->sendRejectionEmail($this->currentRequest->family->email, $isFinal, $finalComment);
        }

        $this->loadNextRequest();
        $this->loadCounts();

        $this->dispatch('scroll-to-top');
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

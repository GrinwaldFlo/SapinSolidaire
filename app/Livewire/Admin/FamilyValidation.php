<?php

namespace App\Livewire\Admin;

use App\Livewire\Admin\Concerns\ChecksDuplicateFamily;
use App\Livewire\Admin\Concerns\HandlesFamilyValidation;
use App\Livewire\Admin\Concerns\InsertsCommentTemplates;
use App\Livewire\Admin\Concerns\ShowsFamilyModal;
use App\Models\Child;
use App\Models\GiftRequest;
use App\Models\Season;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class FamilyValidation extends Component
{
    use ChecksDuplicateFamily, HandlesFamilyValidation, InsertsCommentTemplates, ShowsFamilyModal;

    private const LOCK_TTL_SECONDS = 300;

    public ?Season $activeSeason = null;
    public ?GiftRequest $currentRequest = null;
    public int $pendingFamiliesCount = 0;
    public array $predefinedRejectionMessages = [];
    public string $selectedRejectionMessageKey = '';

    public function mount(): void
    {
        $this->activeSeason = Season::getActive();
        $this->predefinedRejectionMessages = Setting::getValidationCommentTemplates();
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

        $candidateIds = GiftRequest::where('season_id', $this->activeSeason->id)
            ->where('status', GiftRequest::STATUS_PENDING)
            ->orderBy('updated_at')
            ->pluck('id');

        $this->currentRequest = null;

        foreach ($candidateIds as $candidateId) {
            $lockKey = "family_validation_lock:{$candidateId}";

            if (Cache::add($lockKey, $adminId, self::LOCK_TTL_SECONDS)
                || Cache::get($lockKey) === $adminId) {
                Cache::put("family_validation_admin:{$adminId}", $candidateId, self::LOCK_TTL_SECONDS);
                $this->currentRequest = GiftRequest::with('family')->find($candidateId);
                break;
            }
        }

        $this->checkForDuplicates();
    }

    protected function releaseReservation(string $adminId): void
    {
        $previousRequestId = Cache::get("family_validation_admin:{$adminId}");

        if ($previousRequestId) {
            Cache::forget("family_validation_lock:{$previousRequestId}");
            Cache::forget("family_validation_admin:{$adminId}");
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
    }

    public function openRejectionModal(string $id, bool $isFinal = false): void
    {
        $this->rejectionTargetId = $id;
        $this->isFinalRejection = $isFinal;
        $this->rejectionComment = '';
        $this->selectedRejectionMessageKey = '';
        $this->showRejectionModal = true;
    }

    public function applyRejectionMessage(): void
    {
        $template = $this->resolveTemplateMessage($this->selectedRejectionMessageKey);
        if ($template === '') {
            return;
        }

        $this->rejectionComment = $this->appendTemplateMessage($this->rejectionComment, $template);
        $this->resetErrorBag('rejectionComment');
    }

    public function confirmRejection(): void
    {
        $this->validate([
            'rejectionComment' => ['required', 'string', 'min:10'],
        ], [
            'rejectionComment.required' => 'Le commentaire est obligatoire.',
            'rejectionComment.min' => 'Le commentaire doit contenir au moins 10 caractères.',
        ]);

        $status = $this->isFinalRejection ? GiftRequest::STATUS_REJECTED_FINAL : GiftRequest::STATUS_REJECTED;

        $email = DB::transaction(function () use ($status) {
            $request = GiftRequest::with('family')->lockForUpdate()->findOrFail($this->rejectionTargetId);
            $request->setStatus($status, $this->rejectionComment);

            if ($this->isFinalRejection) {
                $request->children()->update([
                    'status' => Child::STATUS_REJECTED_FINAL,
                    'status_changed_at' => now(),
                    'rejection_comment' => $this->rejectionComment,
                ]);
            }

            return (string) $request->family->email;
        });

        $this->sendRejectionEmail($email, $this->isFinalRejection, $this->rejectionComment);

        $this->closeRejectionModal();
        $this->loadNextRequest();
        $this->loadCounts();
    }

    public function skip(): void
    {
        if ($this->currentRequest) {
            $this->currentRequest->touch();
            $this->releaseReservation((string) auth()->id());
        }

        $this->loadNextRequest();
        $this->loadCounts();
    }

    public function render()
    {
        return view('livewire.admin.family-validation');
    }
}

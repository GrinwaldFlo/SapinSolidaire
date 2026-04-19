<?php

namespace App\Livewire\Admin;

use App\Livewire\Admin\Concerns\HandlesFamilyValidation;
use App\Models\GiftRequest;
use App\Models\Season;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class FamilyValidation extends Component
{
    use HandlesFamilyValidation;

    private const LOCK_TTL_SECONDS = 300;

    public ?Season $activeSeason = null;
    public ?GiftRequest $currentRequest = null;
    public int $pendingFamiliesCount = 0;

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

        $candidateIds = GiftRequest::where('season_id', $this->activeSeason->id)
            ->where('status', GiftRequest::STATUS_PENDING)
            ->orderBy('created_at')
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
        $this->showRejectionModal = true;
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

        $request = GiftRequest::with('family')->findOrFail($this->rejectionTargetId);
        $request->setStatus($status, $this->rejectionComment);

        $this->sendRejectionEmail($request->family->email, $this->isFinalRejection, $this->rejectionComment);

        $this->closeRejectionModal();
        $this->loadNextRequest();
        $this->loadCounts();
    }

    public function render()
    {
        return view('livewire.admin.family-validation');
    }
}

<?php

namespace App\Livewire\Admin;

use App\Mail\CorrectionRequestMail;
use App\Mail\FinalRejectionMail;
use App\Models\Child;
use App\Models\EmailToken;
use App\Models\GiftRequest;
use App\Models\Season;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class Validation extends Component
{
    private const LOCK_TTL_SECONDS = 300;

    public ?Season $activeSeason = null;
    public ?GiftRequest $currentRequest = null;
    public int $pendingFamiliesCount = 0;
    public int $pendingChildrenCount = 0;

    // Rejection modal
    public bool $showRejectionModal = false;
    public string $rejectionType = ''; // 'family', 'child'
    public ?string $rejectionTargetId = null;
    public bool $isFinalRejection = false;
    public string $rejectionComment = '';

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
            ->orderBy('created_at')
            ->pluck('id');

        $this->currentRequest = null;

        foreach ($candidateIds as $candidateId) {
            $lockKey = "validation_lock:{$candidateId}";

            // Try to atomically acquire the lock, or reuse our own
            if (Cache::add($lockKey, $adminId, self::LOCK_TTL_SECONDS)
                || Cache::get($lockKey) === $adminId) {
                Cache::put("validation_admin:{$adminId}", $candidateId, self::LOCK_TTL_SECONDS);
                $this->currentRequest = GiftRequest::with(['family', 'children'])->find($candidateId);
                break;
            }
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

    public function validateFamily(): void
    {
        if (! $this->currentRequest) {
            return;
        }

        DB::transaction(function () {
            $request = GiftRequest::lockForUpdate()->find($this->currentRequest->id);

            if (! $request || $request->status !== GiftRequest::STATUS_PENDING) {
                return;
            }

            if ($request->family_number === null) {
                $request->family_number = $this->activeSeason->assignNextFamilyNumber();
                $request->save();
            }

            $request->setStatus(GiftRequest::STATUS_VALIDATED);
            $this->currentRequest = $request;
        });

        $this->loadNextRequest();
        $this->loadCounts();
    }

    public function validateChild(string $childId): void
    {
        DB::transaction(function () use ($childId) {
            $child = Child::lockForUpdate()->find($childId);

            if (! $child || $child->status !== Child::STATUS_PENDING) {
                return;
            }

            $child->assignChildNumberAndCode();
            $child->setStatus(Child::STATUS_VALIDATED);
        });

        $this->loadNextRequest();
        $this->loadCounts();
    }

    public function openRejectionModal(string $type, string $id, bool $isFinal = false): void
    {
        $this->rejectionType = $type;
        $this->rejectionTargetId = $id;
        $this->isFinalRejection = $isFinal;
        $this->rejectionComment = '';
        $this->showRejectionModal = true;
    }

    public function closeRejectionModal(): void
    {
        $this->showRejectionModal = false;
        $this->rejectionType = '';
        $this->rejectionTargetId = null;
        $this->isFinalRejection = false;
        $this->rejectionComment = '';
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

        if ($this->rejectionType === 'family') {
            $request = GiftRequest::with('family')->findOrFail($this->rejectionTargetId);
            $request->setStatus($status, $this->rejectionComment);

            $this->sendRejectionEmail($request->family->email, $this->isFinalRejection, $this->rejectionComment);
        } else {
            $child = Child::with('giftRequest.family')->findOrFail($this->rejectionTargetId);
            $child->setStatus($status === GiftRequest::STATUS_REJECTED_FINAL ? Child::STATUS_REJECTED_FINAL : Child::STATUS_REJECTED, $this->rejectionComment);

            $this->sendRejectionEmail($child->giftRequest->family->email, $this->isFinalRejection, $this->rejectionComment);
        }

        $this->closeRejectionModal();
        $this->loadNextRequest();
        $this->loadCounts();
    }

    protected function sendRejectionEmail(string $email, bool $isFinal, string $comment): void
    {
        if ($isFinal) {
            Mail::to($email)->queue(new FinalRejectionMail($comment));
        } else {
            $token = EmailToken::createForEmail($email);
            Mail::to($email)->queue(new CorrectionRequestMail($email, $token->token, $comment));
        }
    }

    public function render()
    {
        return view('livewire.admin.validation');
    }
}

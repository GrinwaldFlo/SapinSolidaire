<?php

namespace App\Livewire\Admin\Concerns;

use App\Mail\CorrectionRequestMail;
use App\Mail\FinalRejectionMail;
use App\Models\EmailToken;
use App\Models\GiftRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

trait HandlesFamilyValidation
{
    public bool $showRejectionModal = false;
    public ?string $rejectionTargetId = null;
    public bool $isFinalRejection = false;
    public string $rejectionComment = '';

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

    public function closeRejectionModal(): void
    {
        $this->showRejectionModal = false;
        $this->rejectionTargetId = null;
        $this->isFinalRejection = false;
        $this->rejectionComment = '';
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
}

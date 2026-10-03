<?php

namespace App\Livewire\Admin\Concerns;

use App\Mail\CorrectionRequestMail;
use App\Mail\FinalRejectionMail;
use App\Models\AdminActionLog;
use App\Models\EmailToken;
use App\Models\GiftRequest;
use App\Models\SentMailLog;
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

        $validatedRequest = null;

        DB::transaction(function () use (&$validatedRequest) {
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
            $validatedRequest = $request->loadMissing('family');
        });

        if ($validatedRequest instanceof GiftRequest) {
            AdminActionLog::create([
                'user_id' => auth()->id(),
                'user_label' => auth()->user()?->name,
                'action_type' => AdminActionLog::ACTION_FAMILY_VALIDATED,
                'description' => 'Validation famille: '.$validatedRequest->family?->full_name,
                'family_id' => $validatedRequest->family_id,
                'gift_request_id' => $validatedRequest->id,
            ]);
        }

        $this->loadNextRequest();
        $this->loadCounts();
    }

    public function closeRejectionModal(): void
    {
        $this->showRejectionModal = false;
        $this->rejectionTargetId = null;
        $this->isFinalRejection = false;
        $this->rejectionComment = '';

        if (property_exists($this, 'selectedRejectionMessageKey')) {
            $this->selectedRejectionMessageKey = '';
        }
    }

    protected function sendRejectionEmail(string $email, bool $isFinal, string $comment): void
    {
        $request = $this->currentRequest?->loadMissing('family');
        $familyLabel = trim((string) $request?->family?->full_name) !== ''
            ? $request?->family?->full_name
            : $email;

        if ($isFinal) {
            $mail = new FinalRejectionMail($comment);
            Mail::to($email)->queue($mail);
            SentMailLog::logQueuedMail(
                recipientEmail: $email,
                purpose: SentMailLog::PURPOSE_FINAL_REJECTION,
                mailable: $mail,
                sender: auth()->user()
            );

            AdminActionLog::create([
                'user_id' => auth()->id(),
                'user_label' => auth()->user()?->name,
                'action_type' => AdminActionLog::ACTION_EMAIL_SENT,
                'description' => "E-mail envoyé ({$mail->envelope()->subject}) à {$email} (famille: {$familyLabel})",
                'family_id' => $request?->family_id,
                'gift_request_id' => $request?->id,
            ]);
        } else {
            $token = EmailToken::createForEmail($email);
            $mail = new CorrectionRequestMail($email, $token->token, $comment);
            Mail::to($email)->queue($mail);
            SentMailLog::logQueuedMail(
                recipientEmail: $email,
                purpose: SentMailLog::PURPOSE_CORRECTION_REQUEST,
                mailable: $mail,
                sender: auth()->user()
            );

            AdminActionLog::create([
                'user_id' => auth()->id(),
                'user_label' => auth()->user()?->name,
                'action_type' => AdminActionLog::ACTION_EMAIL_SENT,
                'description' => "E-mail envoyé ({$mail->envelope()->subject}) à {$email} (famille: {$familyLabel})",
                'family_id' => $request?->family_id,
                'gift_request_id' => $request?->id,
            ]);
        }
    }
}

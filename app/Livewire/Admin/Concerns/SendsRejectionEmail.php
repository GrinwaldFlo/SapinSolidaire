<?php

namespace App\Livewire\Admin\Concerns;

use App\Mail\CorrectionRequestMail;
use App\Mail\FinalRejectionMail;
use App\Models\AdminActionLog;
use App\Models\EmailToken;
use App\Models\SentMailLog;
use Illuminate\Support\Facades\Mail;

trait SendsRejectionEmail
{
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

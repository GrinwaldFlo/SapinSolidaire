<?php

namespace App\Livewire\Admin\Concerns;

use App\Mail\CorrectionRequestMail;
use App\Mail\FinalRejectionMail;
use App\Models\EmailToken;
use App\Models\GiftRequest;
use App\Models\SentMailLog;
use App\Services\FamilyMailer;

trait SendsRejectionEmail
{
    /**
     * Queue a final rejection or a correction request (with a new access link) to the family of the request.
     */
    protected function sendRejectionEmail(GiftRequest $request, bool $isFinal, string $comment): void
    {
        $request->loadMissing('family');
        $email = $request->family->email;

        if ($isFinal) {
            $mail = new FinalRejectionMail($comment);
            $purpose = SentMailLog::PURPOSE_FINAL_REJECTION;
        } else {
            $token = EmailToken::createForEmail($email);
            $mail = new CorrectionRequestMail($email, $token->token, $comment);
            $purpose = SentMailLog::PURPOSE_CORRECTION_REQUEST;
        }

        app(FamilyMailer::class)->queueForRequest($request, $mail, $purpose);
    }
}

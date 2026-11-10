<?php

namespace App\Services;

use App\Models\AdminActionLog;
use App\Models\GiftRequest;
use App\Models\SentMailLog;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

class FamilyMailer
{
    /**
     * Queue a mail to the family of a gift request and record it in the sent mail and admin action logs.
     */
    public function queueForRequest(GiftRequest $request, Mailable $mail, string $purpose): void
    {
        $familyEmail = $request->family->email;

        Mail::to($familyEmail)->queue($mail);

        SentMailLog::logQueuedMail(
            recipientEmail: $familyEmail,
            purpose: $purpose,
            mailable: $mail,
            sender: auth()->user()
        );

        $familyLabel = trim((string) $request->family->full_name) !== ''
            ? $request->family->full_name
            : $familyEmail;

        AdminActionLog::record(
            AdminActionLog::ACTION_EMAIL_SENT,
            "E-mail envoyé ({$mail->envelope()->subject}) à {$familyEmail} (famille: {$familyLabel})",
            $request->family_id,
            $request->id,
        );
    }
}

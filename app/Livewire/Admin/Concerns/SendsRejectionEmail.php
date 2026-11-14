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
     * Separator between the comments of several children in one correction email.
     */
    protected const COMMENTS_SEPARATOR = "\n-------------------\n";

    /**
     * Queue a final rejection or a correction request (with a new access link) to the family of the request.
     */
    protected function sendRejectionEmail(GiftRequest $request, bool $isFinal, string $comment): void
    {
        if (! $isFinal) {
            $this->sendCorrectionEmail($request, $comment, SentMailLog::PURPOSE_CORRECTION_REQUEST);

            return;
        }

        $request->loadMissing('family');
        app(FamilyMailer::class)->queueForRequest($request, new FinalRejectionMail($comment), SentMailLog::PURPOSE_FINAL_REJECTION);
    }

    /**
     * Queue a correction request with a new 48h access link (the previous link of the family is replaced).
     */
    protected function sendCorrectionEmail(GiftRequest $request, string $comment, string $purpose): void
    {
        $request->loadMissing('family');
        $email = $request->family->email;
        $token = EmailToken::createForEmail($email);

        app(FamilyMailer::class)->queueForRequest($request, new CorrectionRequestMail($email, $token->token, $comment), $purpose);
    }

    /**
     * Comment block of a child asking a correction (or refused when $isFinal), as sent to the family.
     */
    protected static function childRejectionComment(string $firstName, string $comment, bool $isFinal = false): string
    {
        $prefix = $isFinal ? "Refus pour l'enfant {$firstName} :" : "Pour l'enfant {$firstName} :";

        return $prefix."\n - ".$comment;
    }

    /**
     * Join several comment blocks into the comment of one email.
     *
     * @param  array<int, string>  $comments
     */
    protected static function combineRejectionComments(array $comments): string
    {
        return implode(self::COMMENTS_SEPARATOR, $comments);
    }
}

<?php

namespace App\Mail;

use App\Models\Setting;
use App\Services\CustomText;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CorrectionRequestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public string $email,
        public string $token,
        public string $comment
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $replyTo = Setting::getReplyToEmail();

        return new Envelope(
            subject: CustomText::get(CustomText::MAIL_CORRECTION_SUBJECT),
            replyTo: $replyTo ? [$replyTo] : [],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.correction-request',
            text: 'emails.correction-request-text',
            with: [
                'accessUrl' => url("/cadeau/{$this->token}"),
                'comment' => $this->comment,
                'siteName' => Setting::getSiteName(),
                'introText' => CustomText::get(CustomText::MAIL_CORRECTION_INTRO),
                'closingText' => CustomText::get(CustomText::MAIL_CORRECTION_CLOSING),
                'footerText' => CustomText::get(CustomText::MAIL_FOOTER_NO_REPLY),
            ],
        );
    }
}

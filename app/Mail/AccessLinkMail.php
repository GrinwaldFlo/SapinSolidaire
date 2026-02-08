<?php

namespace App\Mail;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccessLinkMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public string $email,
        public string $token
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $replyTo = Setting::getReplyToEmail();

        return new Envelope(
            subject: 'Votre demande de cadeau - '.Setting::getSiteName(),
            replyTo: $replyTo ? [$replyTo] : [],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.access-link',
            with: [
                'accessUrl' => url("/cadeau/{$this->token}"),
                'siteName' => Setting::getSiteName(),
            ],
        );
    }
}

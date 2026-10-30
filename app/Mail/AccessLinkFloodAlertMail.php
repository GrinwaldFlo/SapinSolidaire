<?php

namespace App\Mail;

use App\Models\Setting;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to the administrators when access link requests are blocked after a flood.
 */
class AccessLinkFloodAlertMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public int $requestCount,
        public int $windowMinutes,
        public DateTimeInterface $blockedUntil,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '['.Setting::getSiteName().'] Alerte : demandes de liens d\'accès bloquées',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.access-link-flood-alert',
            text: 'emails.access-link-flood-alert-text',
            with: [
                'siteName' => Setting::getSiteName(),
                'requestCount' => $this->requestCount,
                'windowMinutes' => $this->windowMinutes,
                'blockedUntil' => $this->blockedUntil->format('d.m.Y H:i'),
            ],
        );
    }
}

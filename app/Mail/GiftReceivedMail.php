<?php

namespace App\Mail;

use App\Models\GiftRequest;
use App\Models\Season;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GiftReceivedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public GiftRequest $giftRequest,
        public Season $season
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $replyTo = Setting::getReplyToEmail();
        $familyName = $this->giftRequest->family->last_name ?? '';

        return new Envelope(
            subject: "Les cadeaux sont prêts ! - ".Setting::getSiteName(),
            replyTo: $replyTo ? [$replyTo] : [],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.gift-received',
            with: [
                'familyName' => $this->giftRequest->family->last_name ?? '',
                'slotDate' => $this->giftRequest->slot_start_datetime?->translatedFormat('l d F Y'),
                'slotStartTime' => $this->giftRequest->slot_start_datetime?->format('H:i'),
                'slotEndTime' => $this->giftRequest->slot_end_datetime?->format('H:i'),
                'responsibleName' => $this->season->responsible_name,
                'responsiblePhone' => $this->season->responsible_phone,
                'responsibleEmail' => $this->season->responsible_email,
                'siteName' => Setting::getSiteName(),
            ],
        );
    }
}

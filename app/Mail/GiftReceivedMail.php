<?php

namespace App\Mail;

use App\Models\Child;
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
        public Child $child,
        public Season $season
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $replyTo = Setting::getReplyToEmail();

        return new Envelope(
            subject: "Bonne nouvelle ! Le cadeau de {$this->child->first_name} est arrivé - ".Setting::getSiteName(),
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
                'childName' => $this->child->first_name,
                'gift' => $this->child->gift,
                'code' => $this->child->code,
                'pickupDate' => $this->season->pickup_start_date?->format('d/m/Y'),
                'pickupAddress' => $this->season->pickup_address,
                'siteName' => Setting::getSiteName(),
            ],
        );
    }
}

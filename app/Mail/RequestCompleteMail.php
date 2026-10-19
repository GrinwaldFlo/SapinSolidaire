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
use Illuminate\Support\Collection;

class RequestCompleteMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<int, \App\Models\Child>  $children  Children whose card has just been printed
     */
    public function __construct(
        public GiftRequest $giftRequest,
        public Season $season,
        public Collection $children
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $replyTo = $this->season->responsible_email ?? Setting::getReplyToEmail();

        return new Envelope(
            subject: 'Votre demande est complète - '.Setting::getSiteName(),
            replyTo: $replyTo ? [$replyTo] : [],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.request-complete',
            text: 'emails.request-complete-text',
            with: [
                'familyName' => $this->giftRequest->family->last_name ?? '',
                'childNames' => $this->children->pluck('first_name')->filter()->values(),
                'pickupDateText' => $this->season->pickupDateText(),
                'responsibleName' => $this->season->responsible_name,
                'responsiblePhone' => $this->season->responsible_phone,
                'responsibleEmail' => $this->season->responsible_email,
                'siteName' => Setting::getSiteName(),
            ],
        );
    }
}

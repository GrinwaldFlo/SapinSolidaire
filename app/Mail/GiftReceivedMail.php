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
        $replyTo = $this->season->responsible_email ?? Setting::getReplyToEmail();

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
        $start = $this->giftRequest->slot_start_datetime;
        $end = $this->giftRequest->slot_end_datetime;
        $googleCalendarUrl = null;

        if ($start && $end) {
            $googleCalendarUrl = 'https://calendar.google.com/calendar/render?' . http_build_query([
                'action' => 'TEMPLATE',
                'text' => 'Retrait des cadeaux - ' . Setting::getSiteName(),
                'dates' => $start->utc()->format('Ymd\THis\Z') . '/' . $end->utc()->format('Ymd\THis\Z'),
                'location' => $this->season->pickup_address ?? '',
                'details' => 'N\'oubliez pas votre pièce d\'identité et celles de vos enfants. Pensez à prendre un grand sac.',
            ]);
        }

        return new Content(
            view: 'emails.gift-received',
            with: [
                'familyName' => $this->giftRequest->family->last_name ?? '',
                'slotDate' => $start?->translatedFormat('l d F Y'),
                'slotStartTime' => $start?->format('H:i'),
                'slotEndTime' => $end?->format('H:i'),
                'pickupAddress' => $this->season->pickup_address,
                'responsibleName' => $this->season->responsible_name,
                'responsiblePhone' => $this->season->responsible_phone,
                'responsibleEmail' => $this->season->responsible_email,
                'siteName' => Setting::getSiteName(),
                'googleCalendarUrl' => $googleCalendarUrl,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        $start = $this->giftRequest->slot_start_datetime;
        $end = $this->giftRequest->slot_end_datetime;

        if (!$start || !$end) {
            return [];
        }

        $siteName = Setting::getSiteName();
        $icsContent = $this->generateIcs($start, $end, $siteName);

        return [
            \Illuminate\Mail\Mailables\Attachment::fromData(fn () => $icsContent, 'retrait-cadeaux.ics')
                ->withMime('text/calendar'),
        ];
    }

    private function generateIcs($start, $end, string $siteName): string
    {
        $uid = uniqid('sapin-', true) . '@' . parse_url(config('app.url'), PHP_URL_HOST);
        $location = str_replace(["\r\n", "\n", ","], [' ', ' ', '\,'], $this->season->pickup_address ?? '');
        $summary = str_replace(["\r\n", "\n", ","], [' ', ' ', '\,'], 'Retrait des cadeaux - ' . $siteName);
        $description = str_replace(["\r\n", "\n", ","], [' ', ' ', '\,'], "N'oubliez pas votre pièce d'identité et celles de vos enfants. Pensez à prendre un grand sac.");

        return "BEGIN:VCALENDAR\r\n"
            . "VERSION:2.0\r\n"
            . "PRODID:-//{$siteName}//FR\r\n"
            . "METHOD:PUBLISH\r\n"
            . "BEGIN:VEVENT\r\n"
            . "UID:{$uid}\r\n"
            . "DTSTAMP:" . now()->utc()->format('Ymd\THis\Z') . "\r\n"
            . "DTSTART:" . $start->utc()->format('Ymd\THis\Z') . "\r\n"
            . "DTEND:" . $end->utc()->format('Ymd\THis\Z') . "\r\n"
            . "SUMMARY:{$summary}\r\n"
            . "LOCATION:{$location}\r\n"
            . "DESCRIPTION:{$description}\r\n"
            . "END:VEVENT\r\n"
            . "END:VCALENDAR\r\n";
    }
}

<?php

namespace App\Mail;

use App\Models\GiftRequest;
use App\Models\Season;
use App\Models\Setting;
use App\Services\CustomText;
use App\Support\FrenchDate;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

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
            subject: CustomText::get(CustomText::MAIL_GIFT_RECEIVED_SUBJECT),
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
        $familyName = $this->giftRequest->family->last_name ?? '';

        if ($start && $end) {
            $googleCalendarUrl = 'https://calendar.google.com/calendar/render?'.http_build_query([
                'action' => 'TEMPLATE',
                'text' => 'Retrait des cadeaux - '.Setting::getSiteName(),
                'dates' => $this->formatUtc($start).'/'.$this->formatUtc($end),
                'location' => $this->season->pickup_address ?? '',
                'details' => 'N\'oubliez pas votre pièce d\'identité et celles de vos enfants. Pensez à prendre un grand sac.',
            ]);
        }

        return new Content(
            view: 'emails.gift-received',
            text: 'emails.gift-received-text',
            with: [
                'familyName' => $familyName,
                'introText' => CustomText::get(CustomText::MAIL_GIFT_RECEIVED_INTRO, ['famille' => $familyName]),
                'remindersText' => CustomText::get(CustomText::MAIL_GIFT_RECEIVED_REMINDERS),
                'closingText' => CustomText::get(CustomText::MAIL_GIFT_RECEIVED_CLOSING, ['famille' => $familyName]),
                'slotDate' => FrenchDate::format($start),
                'slotStartTime' => $start?->format('H:i'),
                'slotEndTime' => $end?->format('H:i'),
                'pickupAddress' => $this->season->pickup_address,
                'responsibleName' => $this->season->responsible_name,
                'responsiblePhone' => $this->season->responsible_phone,
                'responsibleEmail' => $this->season->responsible_email,
                'siteName' => Setting::getSiteName(),
                'googleCalendarUrl' => $googleCalendarUrl,
                'footerText' => CustomText::get(CustomText::MAIL_FOOTER),
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

        if (! $start || ! $end) {
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
        $uid = uniqid('sapin-', true).'@'.parse_url(config('app.url'), PHP_URL_HOST);
        $location = str_replace(["\r\n", "\n", ','], [' ', ' ', '\,'], $this->season->pickup_address ?? '');
        $summary = str_replace(["\r\n", "\n", ','], [' ', ' ', '\,'], 'Retrait des cadeaux - '.$siteName);
        $description = str_replace(["\r\n", "\n", ','], [' ', ' ', '\,'], "N'oubliez pas votre pièce d'identité et celles de vos enfants. Pensez à prendre un grand sac.");

        return "BEGIN:VCALENDAR\r\n"
            ."VERSION:2.0\r\n"
            ."PRODID:-//{$siteName}//FR\r\n"
            ."METHOD:PUBLISH\r\n"
            ."BEGIN:VEVENT\r\n"
            ."UID:{$uid}\r\n"
            .'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z')."\r\n"
            .'DTSTART:'.$this->formatUtc($start)."\r\n"
            .'DTEND:'.$this->formatUtc($end)."\r\n"
            ."SUMMARY:{$summary}\r\n"
            ."LOCATION:{$location}\r\n"
            ."DESCRIPTION:{$description}\r\n"
            ."END:VEVENT\r\n"
            ."END:VCALENDAR\r\n";
    }

    /**
     * Slot datetimes hold local wall-clock times (app timezone is UTC):
     * read them in the local timezone before converting to real UTC.
     */
    private function formatUtc(CarbonInterface $dateTime): string
    {
        return Carbon::parse($dateTime->format('Y-m-d H:i:s'), config('app.local_timezone'))
            ->utc()
            ->format('Ymd\THis\Z');
    }
}

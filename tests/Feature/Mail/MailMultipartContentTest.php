<?php

use App\Mail\AccessLinkMail;
use App\Mail\CorrectionRequestMail;
use App\Mail\FinalRejectionMail;
use App\Mail\GiftReceivedMail;
use App\Models\Family;
use App\Models\GiftRequest;
use App\Models\Season;

test('access link mail defines html and text parts', function () {
    $content = (new AccessLinkMail('family@example.com', 'test-token'))->content();

    expect($content->view)->toBe('emails.access-link')
        ->and($content->text)->toBe('emails.access-link-text');
});

test('correction request mail defines html and text parts', function () {
    $content = (new CorrectionRequestMail('family@example.com', 'test-token', 'Merci de corriger les informations.'))->content();

    expect($content->view)->toBe('emails.correction-request')
        ->and($content->text)->toBe('emails.correction-request-text');
});

test('final rejection mail defines html and text parts', function () {
    $content = (new FinalRejectionMail('Votre dossier est incomplet.'))->content();

    expect($content->view)->toBe('emails.final-rejection')
        ->and($content->text)->toBe('emails.final-rejection-text');
});

test('gift received mail defines html and text parts', function () {
    $giftRequest = new GiftRequest([
        'slot_start_datetime' => now()->addDay()->setTime(10, 0),
        'slot_end_datetime' => now()->addDay()->setTime(11, 0),
    ]);
    $giftRequest->setRelation('family', new Family([
        'last_name' => 'Martin',
    ]));

    $season = new Season([
        'pickup_address' => 'Rue de Test 1, 1000 Lausanne',
    ]);

    $content = (new GiftReceivedMail($giftRequest, $season))->content();

    expect($content->view)->toBe('emails.gift-received')
        ->and($content->text)->toBe('emails.gift-received-text');
});

test('rejection comments in html mails have clickable urls and line breaks', function (\Illuminate\Mail\Mailable $mail) {
    $content = $mail->content();
    $html = view($content->view, $content->with)->render();

    expect($html)->toContain("Hors zone.<br />\nVoir <a href=\"https://www.e-f-y.ch\"");
})->with([
    'correction' => fn () => new CorrectionRequestMail('family@example.com', 'token', "Hors zone.\nVoir https://www.e-f-y.ch"),
    'final rejection' => fn () => new FinalRejectionMail("Hors zone.\nVoir https://www.e-f-y.ch"),
]);

test('gift received ics and calendar link convert local slot times to utc', function (string $date, string $expectedStart, string $expectedEnd) {
    config(['app.local_timezone' => 'Europe/Zurich']);

    $giftRequest = new GiftRequest([
        'slot_start_datetime' => "{$date} 10:00:00",
        'slot_end_datetime' => "{$date} 10:30:00",
    ]);
    $giftRequest->setRelation('family', new Family(['last_name' => 'Dupont']));
    $season = new Season(['pickup_address' => 'Rue de Test 1, 1000 Lausanne']);
    $mail = new GiftReceivedMail($giftRequest, $season);

    $ics = $mail->attachments()[0]->attachWith(fn () => null, fn ($data) => $data());
    $content = $mail->content();

    expect($ics)->toContain("DTSTART:{$expectedStart}\r\n")
        ->and($ics)->toContain("DTEND:{$expectedEnd}\r\n")
        ->and($content->with['googleCalendarUrl'])->toContain(urlencode("{$expectedStart}/{$expectedEnd}"))
        ->and($content->with['slotStartTime'])->toBe('10:00')
        ->and($content->with['slotEndTime'])->toBe('10:30');
})->with([
    'winter time' => ['2026-12-15', '20261215T090000Z', '20261215T093000Z'],
    'summer time' => ['2026-07-01', '20260701T080000Z', '20260701T083000Z'],
]);

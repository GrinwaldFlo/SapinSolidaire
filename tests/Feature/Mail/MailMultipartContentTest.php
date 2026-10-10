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

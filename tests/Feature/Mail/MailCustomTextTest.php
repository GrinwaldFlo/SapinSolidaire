<?php

use App\Mail\AccessLinkMail;
use App\Mail\CorrectionRequestMail;
use App\Mail\FinalRejectionMail;
use App\Mail\GiftReceivedMail;
use App\Mail\RequestCompleteMail;
use App\Models\Family;
use App\Models\GiftRequest;
use App\Models\Season;
use App\Models\Setting;
use App\Services\CustomText;

beforeEach(function () {
    Setting::clearCache();
});

function renderMailText(\Illuminate\Mail\Mailable $mail): string
{
    $content = $mail->content();

    return view($content->text, $content->with)->render();
}

function makeGiftRequestWithSeason(): array
{
    $season = Season::create([
        'name' => 'Saison',
        'start_date' => now()->subDays(10),
        'end_date' => now()->addMonth(),
        'pickup_address' => 'Rue de Test 1, 1000 Lausanne',
    ]);

    $family = Family::create([
        'email' => 'martin@example.com',
        'first_name' => 'Anne',
        'last_name' => 'Martin',
        'street_name' => 'Rue du Lac',
        'house_no' => '1',
        'postal_code' => '1400',
        'city' => 'Yverdon-les-Bains',
    ]);

    $giftRequest = GiftRequest::create([
        'family_id' => $family->id,
        'season_id' => $season->id,
        'status' => GiftRequest::STATUS_VALIDATED,
        'slot_start_datetime' => now()->addDay()->setTime(10, 0),
        'slot_end_datetime' => now()->addDay()->setTime(11, 0),
    ]);

    return [$giftRequest, $season];
}

test('access link mail uses default texts', function () {
    $mail = new AccessLinkMail('family@example.com', 'token');

    expect($mail->envelope()->subject)->toBe('Votre demande de cadeau - Sapin Solidaire');
    $mail->assertSeeInHtml(CustomText::default(CustomText::MAIL_ACCESS_LINK_INTRO), false)
        ->assertSeeInHtml('Au nom du comité de Sapin Solidaire<br />', false)
        ->assertSeeInHtml('Vous pouvez y répondre directement si vous avez des questions.', false);

    expect(renderMailText($mail))
        ->toContain("Si vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer cet e-mail.")
        ->toContain(CustomText::default(CustomText::MAIL_FOOTER));
});

test('access link mail uses custom texts with escaped html and kept line breaks', function () {
    Setting::setValue(CustomText::MAIL_ACCESS_LINK_SUBJECT, 'Lien pour {site}');
    Setting::setValue(CustomText::MAIL_ACCESS_LINK_INTRO, "Bonjour <b>vous</b>\nLigne 2");

    $mail = new AccessLinkMail('family@example.com', 'token');

    expect($mail->envelope()->subject)->toBe('Lien pour Sapin Solidaire');
    $mail->assertSeeInHtml('Bonjour &lt;b&gt;vous&lt;/b&gt;<br />', false)
        ->assertSeeInHtml('Ligne 2', false);

    expect(renderMailText($mail))->toContain("Bonjour <b>vous</b>\nLigne 2");
});

test('correction and rejection mails use custom texts', function () {
    Setting::setValue(CustomText::MAIL_CORRECTION_INTRO, 'Merci de corriger.');
    Setting::setValue(CustomText::MAIL_REJECTION_CLOSING, 'Bonnes fêtes.');
    Setting::setValue(CustomText::MAIL_REJECTION_SUBJECT, 'Refus');

    (new CorrectionRequestMail('family@example.com', 'token', 'Adresse'))
        ->assertSeeInHtml('Merci de corriger.');

    $rejection = new FinalRejectionMail('Hors zone');
    expect($rejection->envelope()->subject)->toBe('Refus');
    $rejection->assertSeeInHtml('Bonnes fêtes.')
        ->assertDontSeeInHtml('Nous vous remercions de votre compréhension.');
});

test('urls in custom texts are links in html mails', function () {
    Setting::setValue(CustomText::MAIL_FOOTER, "Le comité\nhttps://www.e-f-y.ch");

    (new FinalRejectionMail('Hors zone'))
        ->assertSeeInHtml("Le comité<br />\n<a href=\"https://www.e-f-y.ch\"", false);
});

test('gift received mail uses custom texts with family placeholder', function () {
    [$giftRequest, $season] = makeGiftRequestWithSeason();
    Setting::setValue(CustomText::MAIL_GIFT_RECEIVED_INTRO, 'Bonjour famille {famille} !');
    Setting::setValue(CustomText::MAIL_GIFT_RECEIVED_REMINDERS, 'Prenez un sac.');
    Setting::setValue(CustomText::MAIL_FOOTER, 'Le comité de {site}');

    $mail = new GiftReceivedMail($giftRequest, $season);

    $mail->assertSeeInHtml('Bonjour famille Martin !')
        ->assertSeeInHtml('Prenez un sac.')
        ->assertSeeInHtml('Le comité de Sapin Solidaire')
        ->assertDontSeeInHtml("N'oubliez pas de prendre", false);

    expect(renderMailText($mail))->toContain('Bonjour famille Martin !')
        ->toContain('Le comité de Sapin Solidaire');
});

test('all mails share the same footer', function () {
    Setting::setValue(CustomText::MAIL_FOOTER, 'Pied commun {site}');
    [$giftRequest, $season] = makeGiftRequestWithSeason();

    $mails = [
        new AccessLinkMail('family@example.com', 'token'),
        new CorrectionRequestMail('family@example.com', 'token', 'Adresse'),
        new FinalRejectionMail('Hors zone'),
        new RequestCompleteMail($giftRequest, $season, collect()),
        new GiftReceivedMail($giftRequest, $season),
    ];

    foreach ($mails as $mail) {
        $mail->assertSeeInHtml('Pied commun Sapin Solidaire');
        expect(renderMailText($mail))->toContain('Pied commun Sapin Solidaire');
    }
});

test('text parts contain raw text without html entities', function () {
    $comment = "L'adresse <rue> & le numéro";

    expect(renderMailText(new CorrectionRequestMail('family@example.com', 'token', $comment)))->toContain($comment)
        ->and(renderMailText(new FinalRejectionMail($comment)))->toContain($comment);

    [$giftRequest, $season] = makeGiftRequestWithSeason();
    $giftRequest->family->update(['last_name' => "D'Andrea"]);

    $text = renderMailText(new GiftReceivedMail($giftRequest->fresh(), $season));

    expect($text)->toContain("Chère famille D'Andrea,")
        ->toContain('https://calendar.google.com/calendar/render?action=TEMPLATE&text=')
        ->not->toContain('&amp;')
        ->not->toContain('&#039;');
});

test('request complete mail fills the pickup date placeholder', function () {
    [$giftRequest, $season] = makeGiftRequestWithSeason();
    $season->pickupSlots()->create([
        'start_datetime' => now()->addDays(5)->setTime(9, 0),
        'end_datetime' => now()->addDays(5)->setTime(12, 0),
    ]);

    $mail = new RequestCompleteMail($giftRequest, $season, collect());

    expect($mail->content()->with['bodyText'])
        ->toContain('cadeaux souhaités '.$season->pickupDateText().' à l\'heure');
});

test('request complete mail collapses the pickup date placeholder when there is no slot', function () {
    [$giftRequest, $season] = makeGiftRequestWithSeason();

    $mail = new RequestCompleteMail($giftRequest, $season, collect());

    expect($mail->content()->with['bodyText'])
        ->toContain("cadeaux souhaités à l'heure");
});

<?php

use App\Models\Setting;
use App\Services\CustomText;

beforeEach(function () {
    Setting::clearCache();
});

test('default text is used when no custom value is stored', function () {
    expect(CustomText::get(CustomText::REGISTRATION_BLOCKED))
        ->toBe('Les inscriptions sont momentanément fermées.');
});

test('default text is used when the stored value is blank', function () {
    Setting::setValue(CustomText::REGISTRATION_BLOCKED, "  \n ");

    expect(CustomText::get(CustomText::REGISTRATION_BLOCKED))
        ->toBe('Les inscriptions sont momentanément fermées.');
});

test('custom value overrides the default text', function () {
    Setting::setValue(CustomText::REGISTRATION_BLOCKED, 'Fermé');

    expect(CustomText::get(CustomText::REGISTRATION_BLOCKED))->toBe('Fermé');
});

test('custom value "0" is not replaced by the default text', function () {
    Setting::setValue(CustomText::REGISTRATION_BLOCKED, '0');

    expect(CustomText::get(CustomText::REGISTRATION_BLOCKED))->toBe('0');
});

test('introduction text has no default', function () {
    expect(CustomText::get(CustomText::HOME_INTRODUCTION))->toBe('');
});

test('site placeholder is always replaced', function () {
    Setting::setValue(Setting::SITE_NAME, 'Noël pour tous');

    expect(CustomText::get(CustomText::MAIL_ACCESS_LINK_SUBJECT))
        ->toBe('Votre demande de cadeau - Noël pour tous');
});

test('given placeholders are replaced and extra spaces of empty ones collapsed', function () {
    Setting::setValue(CustomText::MAIL_REQUEST_COMPLETE_BODY, "Bonjour {famille},\nRetrait {date_retrait} à l'heure indiquée.");

    expect(CustomText::get(CustomText::MAIL_REQUEST_COMPLETE_BODY, ['famille' => 'Martin', 'date_retrait' => '']))
        ->toBe("Bonjour Martin,\nRetrait à l'heure indiquée.");
});

test('spacing entered by the admin is kept', function () {
    Setting::setValue(CustomText::MAIL_GIFT_RECEIVED_REMINDERS, "Rappels :\n  - pièce d'identité\n  - grand  sac");

    expect(CustomText::get(CustomText::MAIL_GIFT_RECEIVED_REMINDERS))
        ->toBe("Rappels :\n  - pièce d'identité\n  - grand  sac");
});

test('replacement values are inserted as is', function () {
    Setting::setValue(CustomText::MAIL_REQUEST_COMPLETE_BODY, 'Bonjour {famille},');

    expect(CustomText::get(CustomText::MAIL_REQUEST_COMPLETE_BODY, ['famille' => 'Da  Silva']))
        ->toBe('Bonjour Da  Silva,');
});

test('empty placeholders at line boundaries leave no extra space', function () {
    Setting::setValue(CustomText::MAIL_REQUEST_COMPLETE_BODY, "{date_retrait} Retrait\nBonjour {famille}");

    expect(CustomText::get(CustomText::MAIL_REQUEST_COMPLETE_BODY, ['famille' => '', 'date_retrait' => '']))
        ->toBe("Retrait\nBonjour");
});

test('unknown placeholders are left untouched', function () {
    Setting::setValue(CustomText::FORM_SUBMITTED, 'Merci {inconnu}');

    expect(CustomText::get(CustomText::FORM_SUBMITTED))->toBe('Merci {inconnu}');
});

test('every text defines a label and a default', function () {
    foreach (CustomText::definitions() as $key => $definition) {
        expect($definition)->toHaveKeys(['label', 'default', 'multiline']);
    }

    expect(CustomText::keys())->toContain(Setting::INTRODUCTION_TEXT, Setting::REGISTRATION_BLOCKED_REASON);
});

test('clear cache forgets custom texts', function () {
    Setting::setValue(CustomText::FORM_SUBMITTED, 'Merci');
    CustomText::get(CustomText::FORM_SUBMITTED);

    Setting::where('key', CustomText::FORM_SUBMITTED)->update(['value' => 'Changé']);
    Setting::clearCache();

    expect(CustomText::get(CustomText::FORM_SUBMITTED))->toBe('Changé');
});

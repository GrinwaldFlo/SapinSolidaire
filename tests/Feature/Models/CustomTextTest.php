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

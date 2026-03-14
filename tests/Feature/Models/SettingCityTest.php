<?php

use App\Models\Setting;

beforeEach(function () {
    Setting::clearCache();
});

test('getAllowedCities returns empty array when no setting exists', function () {
    expect(Setting::getAllowedCities())->toBe([]);
});

test('getAllowedCities returns empty array when setting is empty string', function () {
    Setting::setValue(Setting::ALLOWED_CITIES, '');

    expect(Setting::getAllowedCities())->toBe([]);
});

test('getAllowedCities returns single city', function () {
    Setting::setValue(Setting::ALLOWED_CITIES, 'Lausanne');

    expect(Setting::getAllowedCities())->toBe(['Lausanne']);
});

test('getAllowedCities returns multiple cities', function () {
    Setting::setValue(Setting::ALLOWED_CITIES, 'Lausanne, Morges, Renens');

    expect(Setting::getAllowedCities())->toBe(['Lausanne', 'Morges', 'Renens']);
});

test('getAllowedCities trims whitespace around city names', function () {
    Setting::setValue(Setting::ALLOWED_CITIES, '  Lausanne ,  Morges  , Renens  ');

    expect(Setting::getAllowedCities())->toBe(['Lausanne', 'Morges', 'Renens']);
});

test('isCityAllowed returns true when no cities are configured', function () {
    expect(Setting::isCityAllowed('AnyCity'))->toBeTrue();
});

test('isCityAllowed returns true for an allowed city', function () {
    Setting::setValue(Setting::ALLOWED_CITIES, 'Lausanne, Morges, Renens');

    expect(Setting::isCityAllowed('Morges'))->toBeTrue();
});

test('isCityAllowed returns false for a non-allowed city', function () {
    Setting::setValue(Setting::ALLOWED_CITIES, 'Lausanne, Morges, Renens');

    expect(Setting::isCityAllowed('Genève'))->toBeFalse();
});

test('isCityAllowed is case-sensitive', function () {
    Setting::setValue(Setting::ALLOWED_CITIES, 'Lausanne');

    expect(Setting::isCityAllowed('lausanne'))->toBeFalse();
});

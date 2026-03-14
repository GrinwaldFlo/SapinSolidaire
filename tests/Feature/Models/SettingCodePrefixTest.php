<?php

use App\Models\Setting;

beforeEach(function () {
    Setting::clearCache();
});

test('getCodePrefix returns empty string when no setting exists', function () {
    expect(Setting::getCodePrefix())->toBe('');
});

test('getCodePrefix returns configured prefix', function () {
    Setting::setValue(Setting::CODE_PREFIX, 'Y');

    expect(Setting::getCodePrefix())->toBe('Y');
});

test('getCodePrefix returns multi-character prefix', function () {
    Setting::setValue(Setting::CODE_PREFIX, 'AB');

    expect(Setting::getCodePrefix())->toBe('AB');
});

test('getCodePrefix can be updated', function () {
    Setting::setValue(Setting::CODE_PREFIX, 'X');
    expect(Setting::getCodePrefix())->toBe('X');

    Setting::setValue(Setting::CODE_PREFIX, 'Z');
    expect(Setting::getCodePrefix())->toBe('Z');
});

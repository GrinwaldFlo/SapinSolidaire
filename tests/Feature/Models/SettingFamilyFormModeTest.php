<?php

use App\Models\Setting;

beforeEach(function () {
    Setting::clearCache();
});

test('isFamilyFormMultiStepEnabled returns false by default', function () {
    expect(Setting::isFamilyFormMultiStepEnabled())->toBeFalse();
});

test('isFamilyFormMultiStepEnabled returns true when enabled', function () {
    Setting::setValue(Setting::FAMILY_FORM_MULTI_STEP_ENABLED, '1');

    expect(Setting::isFamilyFormMultiStepEnabled())->toBeTrue();
});

test('isFamilyFormMultiStepEnabled returns false when disabled', function () {
    Setting::setValue(Setting::FAMILY_FORM_MULTI_STEP_ENABLED, '0');

    expect(Setting::isFamilyFormMultiStepEnabled())->toBeFalse();
});

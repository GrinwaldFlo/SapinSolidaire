<?php

use App\Models\Setting;

beforeEach(function () {
    Setting::clearCache();
});

test('isProofOfHabitationEnabled returns false by default', function () {
    expect(Setting::isProofOfHabitationEnabled())->toBeFalse();
});

test('isProofOfHabitationEnabled returns true when enabled', function () {
    Setting::setValue(Setting::PROOF_OF_HABITATION_ENABLED, '1');

    expect(Setting::isProofOfHabitationEnabled())->toBeTrue();
});

test('isProofOfHabitationEnabled returns false when disabled', function () {
    Setting::setValue(Setting::PROOF_OF_HABITATION_ENABLED, '0');

    expect(Setting::isProofOfHabitationEnabled())->toBeFalse();
});

test('isProofOfHabitationEnabled can be toggled', function () {
    Setting::setValue(Setting::PROOF_OF_HABITATION_ENABLED, '1');
    Setting::clearCache();
    expect(Setting::isProofOfHabitationEnabled())->toBeTrue();

    Setting::setValue(Setting::PROOF_OF_HABITATION_ENABLED, '0');
    Setting::clearCache();
    expect(Setting::isProofOfHabitationEnabled())->toBeFalse();
});

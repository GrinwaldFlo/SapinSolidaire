<?php

use App\Livewire\Admin\SettingsManagement;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    Setting::clearCache();

    Role::firstOrCreate(['name' => Role::ADMIN]);
    $this->admin = User::factory()->create();
    $this->admin->assignRole(Role::ADMIN);
});

test('settings page loads familyFormMultiStepEnabled as false by default', function () {
    $this->actingAs($this->admin);

    Livewire::test(SettingsManagement::class)
        ->assertSet('familyFormMultiStepEnabled', false);
});

test('settings page loads familyFormMultiStepEnabled when enabled', function () {
    Setting::setValue(Setting::FAMILY_FORM_MULTI_STEP_ENABLED, '1');
    $this->actingAs($this->admin);

    Livewire::test(SettingsManagement::class)
        ->assertSet('familyFormMultiStepEnabled', true);
});

test('admin can enable and disable family form multi step mode', function () {
    $this->actingAs($this->admin);

    Livewire::test(SettingsManagement::class)
        ->set('siteName', 'Test Site')
        ->set('familyFormMultiStepEnabled', true)
        ->call('save')
        ->assertHasNoErrors();

    Setting::clearCache();
    expect(Setting::isFamilyFormMultiStepEnabled())->toBeTrue();

    Livewire::test(SettingsManagement::class)
        ->set('siteName', 'Test Site')
        ->set('familyFormMultiStepEnabled', false)
        ->call('save')
        ->assertHasNoErrors();

    Setting::clearCache();
    expect(Setting::isFamilyFormMultiStepEnabled())->toBeFalse();
});

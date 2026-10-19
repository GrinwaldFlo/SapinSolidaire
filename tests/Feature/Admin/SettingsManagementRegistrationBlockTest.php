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
    $this->actingAs($this->admin);
});

test('registrations are not blocked by default', function () {
    Livewire::test(SettingsManagement::class)
        ->assertSet('registrationBlocked', false)
        ->assertSet('registrationBlockedReason', '')
        ->assertDontSee('Motif du blocage');
});

test('reason field is shown only when blocking is enabled', function () {
    Livewire::test(SettingsManagement::class)
        ->set('registrationBlocked', true)
        ->assertSee('Motif du blocage');
});

test('admin can block registrations with a multiline reason', function () {
    Livewire::test(SettingsManagement::class)
        ->set('registrationBlocked', true)
        ->set('registrationBlockedReason', "  Quota atteint.\nMerci.  ")
        ->call('save')
        ->assertHasNoErrors();

    Setting::clearCache();
    expect(Setting::isRegistrationBlocked())->toBeTrue();
    expect(Setting::getRegistrationBlockedReason())->toBe("Quota atteint.\nMerci.");
});

test('settings page loads the stored block values', function () {
    Setting::setValue(Setting::REGISTRATION_BLOCKED, '1');
    Setting::setValue(Setting::REGISTRATION_BLOCKED_REASON, 'Fermé');

    Livewire::test(SettingsManagement::class)
        ->assertSet('registrationBlocked', true)
        ->assertSet('registrationBlockedReason', 'Fermé');
});

test('admin can unblock registrations', function () {
    Setting::setValue(Setting::REGISTRATION_BLOCKED, '1');

    Livewire::test(SettingsManagement::class)
        ->set('registrationBlocked', false)
        ->call('save')
        ->assertHasNoErrors();

    Setting::clearCache();
    expect(Setting::isRegistrationBlocked())->toBeFalse();
});

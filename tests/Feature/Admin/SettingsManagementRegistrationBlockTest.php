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
        ->assertDontSee('Motif du blocage');
});

test('admin can block registrations', function () {
    Livewire::test(SettingsManagement::class)
        ->set('registrationBlocked', true)
        ->call('save')
        ->assertHasNoErrors();

    Setting::clearCache();
    expect(Setting::isRegistrationBlocked())->toBeTrue();
});

test('settings page loads the stored block value', function () {
    Setting::setValue(Setting::REGISTRATION_BLOCKED, '1');

    Livewire::test(SettingsManagement::class)
        ->assertSet('registrationBlocked', true);
});

test('settings page links to the custom texts page for the block reason', function () {
    Livewire::test(SettingsManagement::class)
        ->assertSee(route('admin.texts-management'));
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

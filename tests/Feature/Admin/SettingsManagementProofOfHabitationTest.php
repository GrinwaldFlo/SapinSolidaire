<?php

use App\Livewire\Admin\SettingsManagement;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    Setting::clearCache();

    // Create admin role and user
    Role::firstOrCreate(['name' => Role::ADMIN]);
    $this->admin = User::factory()->create();
    $this->admin->assignRole(Role::ADMIN);
});

test('settings page loads proofOfHabitationEnabled as false by default', function () {
    $this->actingAs($this->admin);

    Livewire::test(SettingsManagement::class)
        ->assertSet('proofOfHabitationEnabled', false);
});

test('settings page loads proofOfHabitationEnabled when enabled', function () {
    Setting::setValue(Setting::PROOF_OF_HABITATION_ENABLED, '1');

    $this->actingAs($this->admin);

    Livewire::test(SettingsManagement::class)
        ->assertSet('proofOfHabitationEnabled', true);
});

test('admin can enable proof of habitation', function () {
    $this->actingAs($this->admin);

    Livewire::test(SettingsManagement::class)
        ->set('siteName', 'Test Site')
        ->set('proofOfHabitationEnabled', true)
        ->call('save')
        ->assertHasNoErrors();

    Setting::clearCache();
    expect(Setting::isProofOfHabitationEnabled())->toBeTrue();
});

test('admin can disable proof of habitation', function () {
    Setting::setValue(Setting::PROOF_OF_HABITATION_ENABLED, '1');

    $this->actingAs($this->admin);

    Livewire::test(SettingsManagement::class)
        ->set('siteName', 'Test Site')
        ->set('proofOfHabitationEnabled', false)
        ->call('save')
        ->assertHasNoErrors();

    Setting::clearCache();
    expect(Setting::isProofOfHabitationEnabled())->toBeFalse();
});

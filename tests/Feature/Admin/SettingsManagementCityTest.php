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

test('settings page loads allowedCities value', function () {
    Setting::setValue(Setting::ALLOWED_CITIES, 'Lausanne, Morges');

    $this->actingAs($this->admin);

    Livewire::test(SettingsManagement::class)
        ->assertSet('allowedCities', 'Lausanne, Morges');
});

test('settings page loads empty allowedCities when not set', function () {
    $this->actingAs($this->admin);

    Livewire::test(SettingsManagement::class)
        ->assertSet('allowedCities', '');
});

test('admin can save allowedCities setting', function () {
    $this->actingAs($this->admin);

    Livewire::test(SettingsManagement::class)
        ->set('siteName', 'Test Site')
        ->set('allowedCities', 'Lausanne, Morges, Renens')
        ->call('save')
        ->assertHasNoErrors();

    Setting::clearCache();
    expect(Setting::getAllowedCities())->toBe(['Lausanne', 'Morges', 'Renens']);
});

test('admin can save empty allowedCities to allow all cities', function () {
    Setting::setValue(Setting::ALLOWED_CITIES, 'Lausanne');

    $this->actingAs($this->admin);

    Livewire::test(SettingsManagement::class)
        ->set('siteName', 'Test Site')
        ->set('allowedCities', '')
        ->call('save')
        ->assertHasNoErrors();

    Setting::clearCache();
    expect(Setting::getAllowedCities())->toBe([]);
});

test('save displays success message', function () {
    $this->actingAs($this->admin);

    Livewire::test(SettingsManagement::class)
        ->set('siteName', 'Test Site')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('Paramètres enregistrés avec succès.');
});

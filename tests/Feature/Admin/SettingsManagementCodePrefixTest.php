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

test('settings page loads codePrefix value', function () {
    Setting::setValue(Setting::CODE_PREFIX, 'Y');

    $this->actingAs($this->admin);

    Livewire::test(SettingsManagement::class)
        ->assertSet('codePrefix', 'Y');
});

test('settings page loads empty codePrefix when not set', function () {
    $this->actingAs($this->admin);

    Livewire::test(SettingsManagement::class)
        ->assertSet('codePrefix', '');
});

test('admin can save codePrefix setting', function () {
    $this->actingAs($this->admin);

    Livewire::test(SettingsManagement::class)
        ->set('siteName', 'Test Site')
        ->set('codePrefix', 'AB')
        ->call('save')
        ->assertHasNoErrors();

    Setting::clearCache();
    expect(Setting::getCodePrefix())->toBe('AB');
});

test('admin can save empty codePrefix', function () {
    Setting::setValue(Setting::CODE_PREFIX, 'Y');

    $this->actingAs($this->admin);

    Livewire::test(SettingsManagement::class)
        ->set('siteName', 'Test Site')
        ->set('codePrefix', '')
        ->call('save')
        ->assertHasNoErrors();

    Setting::clearCache();
    expect(Setting::getCodePrefix())->toBe('');
});

test('codePrefix rejects values longer than 10 characters', function () {
    $this->actingAs($this->admin);

    Livewire::test(SettingsManagement::class)
        ->set('siteName', 'Test Site')
        ->set('codePrefix', 'ABCDEFGHIJK')
        ->call('save')
        ->assertHasErrors(['codePrefix']);
});

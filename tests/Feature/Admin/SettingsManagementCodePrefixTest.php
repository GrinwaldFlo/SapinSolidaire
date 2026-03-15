<?php

use App\Livewire\Admin\SettingsManagement;
use App\Models\Child;
use App\Models\Family;
use App\Models\GiftRequest;
use App\Models\Role;
use App\Models\Season;
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

test('settings page loads codeFamilyPadding value', function () {
    Setting::setValue(Setting::CODE_FAMILY_PADDING, 6);

    $this->actingAs($this->admin);

    Livewire::test(SettingsManagement::class)
        ->assertSet('codeFamilyPadding', 6);
});

test('settings page loads default codeFamilyPadding when not set', function () {
    $this->actingAs($this->admin);

    Livewire::test(SettingsManagement::class)
        ->assertSet('codeFamilyPadding', 4);
});

test('admin can save codeFamilyPadding setting', function () {
    $this->actingAs($this->admin);

    Livewire::test(SettingsManagement::class)
        ->set('siteName', 'Test Site')
        ->set('codeFamilyPadding', 6)
        ->call('save')
        ->assertHasNoErrors();

    Setting::clearCache();
    expect(Setting::getCodeFamilyPadding())->toBe(6);
});

test('codeFamilyPadding rejects value of 0', function () {
    $this->actingAs($this->admin);

    Livewire::test(SettingsManagement::class)
        ->set('siteName', 'Test Site')
        ->set('codeFamilyPadding', 0)
        ->call('save')
        ->assertHasErrors(['codeFamilyPadding']);
});

test('codeFamilyPadding rejects value greater than 10', function () {
    $this->actingAs($this->admin);

    Livewire::test(SettingsManagement::class)
        ->set('siteName', 'Test Site')
        ->set('codeFamilyPadding', 11)
        ->call('save')
        ->assertHasErrors(['codeFamilyPadding']);
});

test('changing padding regenerates all existing children codes', function () {
    Setting::setValue(Setting::CODE_PREFIX, 'Y');
    Setting::setValue(Setting::CODE_FAMILY_PADDING, 4);

    $season = Season::create([
        'name' => 'Noël 2025',
        'start_date' => now()->subDays(10),
        'end_date' => now()->addDays(30),
        'next_family_number' => 3,
    ]);

    $family = Family::create([
        'email' => 'test@example.com',
        'first_name' => 'Jean',
        'last_name' => 'Dupont',
        'address' => 'Rue de la Gare 1',
        'postal_code' => '1000',
        'city' => 'Lausanne',
        'phone' => '0791234567',
    ]);

    $giftRequest = GiftRequest::create([
        'family_id' => $family->id,
        'season_id' => $season->id,
        'family_number' => 1,
        'status' => GiftRequest::STATUS_VALIDATED,
    ]);

    $child1 = Child::create([
        'gift_request_id' => $giftRequest->id,
        'first_name' => 'Alice',
        'gender' => Child::GENDER_GIRL,
        'birth_year' => 2018,
        'gift' => 'Poupée',
        'child_number' => 1,
        'code' => 'Y0001/1',
        'status' => Child::STATUS_VALIDATED,
    ]);

    $child2 = Child::create([
        'gift_request_id' => $giftRequest->id,
        'first_name' => 'Bob',
        'gender' => Child::GENDER_BOY,
        'birth_year' => 2016,
        'gift' => 'Lego',
        'child_number' => 2,
        'code' => 'Y0001/2',
        'status' => Child::STATUS_VALIDATED,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(SettingsManagement::class)
        ->set('siteName', 'Test Site')
        ->set('codeFamilyPadding', 6)
        ->call('save')
        ->assertHasNoErrors();

    $child1->refresh();
    $child2->refresh();

    expect($child1->code)->toBe('Y000001/1');
    expect($child2->code)->toBe('Y000001/2');
});

test('changing prefix regenerates all existing children codes', function () {
    Setting::setValue(Setting::CODE_PREFIX, 'Y');
    Setting::setValue(Setting::CODE_FAMILY_PADDING, 4);

    $season = Season::create([
        'name' => 'Noël 2025',
        'start_date' => now()->subDays(10),
        'end_date' => now()->addDays(30),
        'next_family_number' => 2,
    ]);

    $family = Family::create([
        'email' => 'test@example.com',
        'first_name' => 'Jean',
        'last_name' => 'Dupont',
        'address' => 'Rue de la Gare 1',
        'postal_code' => '1000',
        'city' => 'Lausanne',
        'phone' => '0791234567',
    ]);

    $giftRequest = GiftRequest::create([
        'family_id' => $family->id,
        'season_id' => $season->id,
        'family_number' => 1,
        'status' => GiftRequest::STATUS_VALIDATED,
    ]);

    $child = Child::create([
        'gift_request_id' => $giftRequest->id,
        'first_name' => 'Alice',
        'gender' => Child::GENDER_GIRL,
        'birth_year' => 2018,
        'gift' => 'Poupée',
        'child_number' => 1,
        'code' => 'Y0001/1',
        'status' => Child::STATUS_VALIDATED,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(SettingsManagement::class)
        ->set('siteName', 'Test Site')
        ->set('codePrefix', 'Z')
        ->call('save')
        ->assertHasNoErrors();

    $child->refresh();
    expect($child->code)->toBe('Z0001/1');
});

test('changing both prefix and padding regenerates all codes correctly', function () {
    Setting::setValue(Setting::CODE_PREFIX, 'Y');
    Setting::setValue(Setting::CODE_FAMILY_PADDING, 4);

    $season = Season::create([
        'name' => 'Noël 2025',
        'start_date' => now()->subDays(10),
        'end_date' => now()->addDays(30),
        'next_family_number' => 3,
    ]);

    $family = Family::create([
        'email' => 'test@example.com',
        'first_name' => 'Jean',
        'last_name' => 'Dupont',
        'address' => 'Rue de la Gare 1',
        'postal_code' => '1000',
        'city' => 'Lausanne',
        'phone' => '0791234567',
    ]);

    $giftRequest = GiftRequest::create([
        'family_id' => $family->id,
        'season_id' => $season->id,
        'family_number' => 12,
        'status' => GiftRequest::STATUS_VALIDATED,
    ]);

    $child = Child::create([
        'gift_request_id' => $giftRequest->id,
        'first_name' => 'Alice',
        'gender' => Child::GENDER_GIRL,
        'birth_year' => 2018,
        'gift' => 'Poupée',
        'child_number' => 1,
        'code' => 'Y0012/1',
        'status' => Child::STATUS_VALIDATED,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(SettingsManagement::class)
        ->set('siteName', 'Test Site')
        ->set('codePrefix', 'AB')
        ->set('codeFamilyPadding', 6)
        ->call('save')
        ->assertHasNoErrors();

    $child->refresh();
    expect($child->code)->toBe('AB000012/1');
});

test('saving without changing prefix or padding does not regenerate codes', function () {
    Setting::setValue(Setting::CODE_PREFIX, 'Y');
    Setting::setValue(Setting::CODE_FAMILY_PADDING, 4);

    $season = Season::create([
        'name' => 'Noël 2025',
        'start_date' => now()->subDays(10),
        'end_date' => now()->addDays(30),
        'next_family_number' => 2,
    ]);

    $family = Family::create([
        'email' => 'test@example.com',
        'first_name' => 'Jean',
        'last_name' => 'Dupont',
        'address' => 'Rue de la Gare 1',
        'postal_code' => '1000',
        'city' => 'Lausanne',
        'phone' => '0791234567',
    ]);

    $giftRequest = GiftRequest::create([
        'family_id' => $family->id,
        'season_id' => $season->id,
        'family_number' => 1,
        'status' => GiftRequest::STATUS_VALIDATED,
    ]);

    $child = Child::create([
        'gift_request_id' => $giftRequest->id,
        'first_name' => 'Alice',
        'gender' => Child::GENDER_GIRL,
        'birth_year' => 2018,
        'gift' => 'Poupée',
        'child_number' => 1,
        'code' => 'Y0001/1',
        'status' => Child::STATUS_VALIDATED,
    ]);

    $originalUpdatedAt = $child->updated_at;

    // Travel forward so updated_at would change if the record is touched
    $this->travel(5)->seconds();

    $this->actingAs($this->admin);

    Livewire::test(SettingsManagement::class)
        ->set('siteName', 'Test Site')
        ->set('codePrefix', 'Y')
        ->set('codeFamilyPadding', 4)
        ->call('save')
        ->assertHasNoErrors();

    $child->refresh();
    expect($child->code)->toBe('Y0001/1');
    expect($child->updated_at->toDateTimeString())->toBe($originalUpdatedAt->toDateTimeString());
});

test('children without code are not affected by regeneration', function () {
    Setting::setValue(Setting::CODE_PREFIX, 'Y');
    Setting::setValue(Setting::CODE_FAMILY_PADDING, 4);

    $season = Season::create([
        'name' => 'Noël 2025',
        'start_date' => now()->subDays(10),
        'end_date' => now()->addDays(30),
        'next_family_number' => 2,
    ]);

    $family = Family::create([
        'email' => 'test@example.com',
        'first_name' => 'Jean',
        'last_name' => 'Dupont',
        'address' => 'Rue de la Gare 1',
        'postal_code' => '1000',
        'city' => 'Lausanne',
        'phone' => '0791234567',
    ]);

    $giftRequest = GiftRequest::create([
        'family_id' => $family->id,
        'season_id' => $season->id,
        'family_number' => 1,
        'status' => GiftRequest::STATUS_VALIDATED,
    ]);

    $pendingChild = Child::create([
        'gift_request_id' => $giftRequest->id,
        'first_name' => 'Alice',
        'gender' => Child::GENDER_GIRL,
        'birth_year' => 2018,
        'gift' => 'Poupée',
        'status' => Child::STATUS_PENDING,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(SettingsManagement::class)
        ->set('siteName', 'Test Site')
        ->set('codeFamilyPadding', 6)
        ->call('save')
        ->assertHasNoErrors();

    $pendingChild->refresh();
    expect($pendingChild->code)->toBeNull();
    expect($pendingChild->child_number)->toBeNull();
});

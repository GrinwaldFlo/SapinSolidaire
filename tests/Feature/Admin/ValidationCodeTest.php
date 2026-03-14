<?php

use App\Livewire\Admin\Validation;
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

    $this->season = Season::create([
        'name' => 'Noël 2025',
        'start_date' => now()->subDays(10),
        'end_date' => now()->addDays(30),
        'next_family_number' => 1,
    ]);

    $this->family = Family::create([
        'email' => 'test@example.com',
        'first_name' => 'Jean',
        'last_name' => 'Dupont',
        'address' => 'Rue de la Gare 1',
        'postal_code' => '1000',
        'city' => 'Lausanne',
        'phone' => '0791234567',
    ]);

    $this->giftRequest = GiftRequest::create([
        'family_id' => $this->family->id,
        'season_id' => $this->season->id,
        'status' => GiftRequest::STATUS_PENDING,
    ]);

    Setting::setValue(Setting::CODE_PREFIX, 'Y');
});

test('validateFamily assigns family number from season counter', function () {
    $this->actingAs($this->admin);

    Livewire::test(Validation::class)
        ->call('validateFamily');

    $this->giftRequest->refresh();
    expect($this->giftRequest->family_number)->toBe(1);
    expect($this->giftRequest->status)->toBe(GiftRequest::STATUS_VALIDATED);
});

test('validateFamily assigns sequential family numbers to different families', function () {
    $family2 = Family::create([
        'email' => 'family2@example.com',
        'first_name' => 'Marie',
        'last_name' => 'Martin',
        'address' => 'Rue du Lac 5',
        'postal_code' => '1000',
        'city' => 'Lausanne',
        'phone' => '0799999999',
    ]);

    $giftRequest2 = GiftRequest::create([
        'family_id' => $family2->id,
        'season_id' => $this->season->id,
        'status' => GiftRequest::STATUS_PENDING,
    ]);

    $this->actingAs($this->admin);

    // Validate first family
    Livewire::test(Validation::class)
        ->call('validateFamily');

    // Validate second family
    Livewire::test(Validation::class)
        ->call('validateFamily');

    $this->giftRequest->refresh();
    $giftRequest2->refresh();

    expect($this->giftRequest->family_number)->toBe(1);
    expect($giftRequest2->family_number)->toBe(2);
});

test('validateFamily does not reassign family number if already set', function () {
    $this->giftRequest->update([
        'family_number' => 42,
        'status' => GiftRequest::STATUS_PENDING,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(Validation::class)
        ->call('validateFamily');

    $this->giftRequest->refresh();
    expect($this->giftRequest->family_number)->toBe(42);

    // Season counter should NOT have been consumed
    $this->season->refresh();
    expect($this->season->next_family_number)->toBe(1);
});

test('validateChild assigns child number and code', function () {
    $this->giftRequest->update([
        'family_number' => 3,
        'status' => GiftRequest::STATUS_VALIDATED,
    ]);

    $child = Child::create([
        'gift_request_id' => $this->giftRequest->id,
        'first_name' => 'Alice',
        'gender' => Child::GENDER_GIRL,
        'birth_year' => 2018,
        'gift' => 'Poupée',
        'status' => Child::STATUS_PENDING,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(Validation::class)
        ->call('validateChild', $child->id);

    $child->refresh();
    expect($child->child_number)->toBe(1);
    expect($child->code)->toBe('Y0003/1');
    expect($child->status)->toBe(Child::STATUS_VALIDATED);
});

test('validateChild assigns sequential child numbers within same family', function () {
    $this->giftRequest->update([
        'family_number' => 1,
        'status' => GiftRequest::STATUS_VALIDATED,
    ]);

    $child1 = Child::create([
        'gift_request_id' => $this->giftRequest->id,
        'first_name' => 'Alice',
        'gender' => Child::GENDER_GIRL,
        'birth_year' => 2018,
        'gift' => 'Poupée',
        'status' => Child::STATUS_PENDING,
    ]);

    $child2 = Child::create([
        'gift_request_id' => $this->giftRequest->id,
        'first_name' => 'Bob',
        'gender' => Child::GENDER_BOY,
        'birth_year' => 2016,
        'gift' => 'Lego',
        'status' => Child::STATUS_PENDING,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(Validation::class)
        ->call('validateChild', $child1->id);

    Livewire::test(Validation::class)
        ->call('validateChild', $child2->id);

    $child1->refresh();
    $child2->refresh();

    expect($child1->child_number)->toBe(1);
    expect($child1->code)->toBe('Y0001/1');

    expect($child2->child_number)->toBe(2);
    expect($child2->code)->toBe('Y0001/2');
});

test('full validation flow produces correct codes', function () {
    Setting::setValue(Setting::CODE_PREFIX, 'Y');

    // Create children for first family
    $child1 = Child::create([
        'gift_request_id' => $this->giftRequest->id,
        'first_name' => 'Alice',
        'gender' => Child::GENDER_GIRL,
        'birth_year' => 2018,
        'gift' => 'Poupée',
        'status' => Child::STATUS_PENDING,
    ]);

    $child2 = Child::create([
        'gift_request_id' => $this->giftRequest->id,
        'first_name' => 'Bob',
        'gender' => Child::GENDER_BOY,
        'birth_year' => 2016,
        'gift' => 'Lego',
        'status' => Child::STATUS_PENDING,
    ]);

    // Create second family
    $family2 = Family::create([
        'email' => 'family2@example.com',
        'first_name' => 'Marie',
        'last_name' => 'Martin',
        'address' => 'Rue du Lac 5',
        'postal_code' => '1000',
        'city' => 'Lausanne',
        'phone' => '0799999999',
    ]);

    $giftRequest2 = GiftRequest::create([
        'family_id' => $family2->id,
        'season_id' => $this->season->id,
        'status' => GiftRequest::STATUS_PENDING,
    ]);

    $child3 = Child::create([
        'gift_request_id' => $giftRequest2->id,
        'first_name' => 'Charlie',
        'gender' => Child::GENDER_BOY,
        'birth_year' => 2019,
        'gift' => 'Peluche',
        'status' => Child::STATUS_PENDING,
    ]);

    $this->actingAs($this->admin);

    // Validate first family, then its children
    Livewire::test(Validation::class)->call('validateFamily');
    Livewire::test(Validation::class)->call('validateChild', $child1->id);
    Livewire::test(Validation::class)->call('validateChild', $child2->id);

    // Validate second family, then its child
    Livewire::test(Validation::class)->call('validateFamily');
    Livewire::test(Validation::class)->call('validateChild', $child3->id);

    $child1->refresh();
    $child2->refresh();
    $child3->refresh();

    // Family 1 (family_number=1): children Y0001/1, Y0001/2
    expect($child1->code)->toBe('Y0001/1');
    expect($child2->code)->toBe('Y0001/2');

    // Family 2 (family_number=2): child Y0002/1
    expect($child3->code)->toBe('Y0002/1');

    // Season counter advanced to 3
    $this->season->refresh();
    expect($this->season->next_family_number)->toBe(3);
});

test('validateChild sets validated_at timestamp', function () {
    $this->giftRequest->update([
        'family_number' => 1,
        'status' => GiftRequest::STATUS_VALIDATED,
    ]);

    $child = Child::create([
        'gift_request_id' => $this->giftRequest->id,
        'first_name' => 'Alice',
        'gender' => Child::GENDER_GIRL,
        'birth_year' => 2018,
        'gift' => 'Poupée',
        'status' => Child::STATUS_PENDING,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(Validation::class)
        ->call('validateChild', $child->id);

    $child->refresh();
    expect($child->validated_at)->not->toBeNull();
});

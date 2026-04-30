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
        'street_name' => 'Rue de la Gare',
        'house_no' => '1',
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
        ->set('familyDecision', 'validated')
        ->call('submitValidation');

    $this->giftRequest->refresh();
    expect($this->giftRequest->family_number)->toBe(1);
    expect($this->giftRequest->status)->toBe(GiftRequest::STATUS_VALIDATED);
});

test('validateFamily assigns sequential family numbers to different families', function () {
    $family2 = Family::create([
        'email' => 'family2@example.com',
        'first_name' => 'Marie',
        'last_name' => 'Martin',
        'street_name' => 'Rue du Lac',
        'house_no' => '5',
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
        ->set('familyDecision', 'validated')
        ->call('submitValidation');

    // Validate second family
    Livewire::test(Validation::class)
        ->set('familyDecision', 'validated')
        ->call('submitValidation');

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
        ->set('familyDecision', 'validated')
        ->call('submitValidation');

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
        ->set('familyDecision', 'validated')
        ->set("childDecisions.{$child->id}", 'validated')
        ->call('submitValidation');

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
        ->set('familyDecision', 'validated')
        ->set("childDecisions.{$child1->id}", 'validated')
        ->set("childDecisions.{$child2->id}", 'validated')
        ->call('submitValidation');

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
        'street_name' => 'Rue du Lac',
        'house_no' => '5',
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
    Livewire::test(Validation::class)
        ->set('familyDecision', 'validated')
        ->set("childDecisions.{$child1->id}", 'validated')
        ->set("childDecisions.{$child2->id}", 'validated')
        ->call('submitValidation');

    // Validate second family, then its child
    // In our component, loadNextRequest loads the next request, so we need a fresh test instance to simulate next page logic or set currentRequest properly manually.
    // However, Livewire component state carries over, so we need to set the state for the new current request.
    Livewire::test(Validation::class)
        ->set('familyDecision', 'validated')
        ->set("childDecisions.{$child3->id}", 'validated')
        ->call('submitValidation');

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
        ->set('familyDecision', 'validated')
        ->set("childDecisions.{$child->id}", 'validated')
        ->call('submitValidation');

    $child->refresh();
    expect($child->validated_at)->not->toBeNull();
});

test('validateChild assigns code even when family is not yet validated', function () {
    $child = Child::create([
        'gift_request_id' => $this->giftRequest->id,
        'first_name' => 'Alice',
        'gender' => Child::GENDER_GIRL,
        'birth_year' => 2018,
        'gift' => 'Poupée',
        'status' => Child::STATUS_PENDING,
    ]);

    $this->actingAs($this->admin);

    // Validate the child BEFORE the family is validated (no family_number yet)
    Livewire::test(Validation::class)
        ->set('familyDecision', 'pending')
        ->set("childDecisions.{$child->id}", 'validated')
        ->call('submitValidation');

    $child->refresh();
    $this->giftRequest->refresh();

    expect($child->status)->toBe(Child::STATUS_VALIDATED);
    expect($this->giftRequest->family_number)->toBe(1);
    expect($child->child_number)->toBe(1);
    expect($child->code)->toBe('Y0001/1');
});

test('validateChild before family then validateFamily does not reassign family number', function () {
    $child = Child::create([
        'gift_request_id' => $this->giftRequest->id,
        'first_name' => 'Alice',
        'gender' => Child::GENDER_GIRL,
        'birth_year' => 2018,
        'gift' => 'Poupée',
        'status' => Child::STATUS_PENDING,
    ]);

    $this->actingAs($this->admin);

    // Validate child first (auto-assigns family_number = 1)
    Livewire::test(Validation::class)
        ->set('familyDecision', 'pending')
        ->set("childDecisions.{$child->id}", 'validated')
        ->call('submitValidation');

    // Then validate family — should not consume a new family number
    // Set up mock for request since current iteration advances the cursor
    // However for this test we only check the data logic, so re-evaluating the object is fine if it works using direct assign
    $this->giftRequest->refresh();
    $this->giftRequest->setStatus(GiftRequest::STATUS_PENDING);
    
    Livewire::test(Validation::class) // Loads next request, which might be same if pending
        ->set('familyDecision', 'validated')
        ->set("childDecisions.{$child->id}", 'validated')
        ->call('submitValidation');

    $child->refresh();
    $this->giftRequest->refresh();
    $this->season->refresh();

    expect($this->giftRequest->family_number)->toBe(1);
    expect($child->code)->toBe('Y0001/1');
    expect($this->season->next_family_number)->toBe(2);
});

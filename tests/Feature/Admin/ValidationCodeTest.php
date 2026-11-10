<?php

use App\Livewire\Admin\FamilyValidation;
use App\Livewire\Admin\Validation;
use App\Mail\CorrectionRequestMail;
use App\Models\Child;
use App\Models\Family;
use App\Models\GiftRequest;
use App\Models\Role;
use App\Models\Season;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
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

    $this->makeChild = fn (GiftRequest $request, string $firstName, string $status = Child::STATUS_PENDING) => Child::create([
        'gift_request_id' => $request->id,
        'first_name' => $firstName,
        'gender' => Child::GENDER_GIRL,
        'birth_year' => 2018,
        'gift' => 'Poupée',
        'status' => $status,
    ]);
});

test('validateFamily assigns family number from season counter', function () {
    $this->actingAs($this->admin);

    Livewire::test(FamilyValidation::class)->call('validateFamily');

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

    // validateFamily moves to the next pending family
    Livewire::test(FamilyValidation::class)
        ->call('validateFamily')
        ->call('validateFamily');

    $this->giftRequest->refresh();
    $giftRequest2->refresh();

    expect($this->giftRequest->family_number)->toBe(1);
    expect($giftRequest2->family_number)->toBe(2);
});

test('validateFamily does not reassign family number if already set', function () {
    $this->giftRequest->update(['family_number' => 42]);

    $this->actingAs($this->admin);

    Livewire::test(FamilyValidation::class)->call('validateFamily');

    $this->giftRequest->refresh();
    expect($this->giftRequest->family_number)->toBe(42);

    // Season counter should NOT have been consumed
    $this->season->refresh();
    expect($this->season->next_family_number)->toBe(1);
});

test('gift validation does not list requests of families still pending', function () {
    ($this->makeChild)($this->giftRequest, 'Alice');

    $this->actingAs($this->admin);

    Livewire::test(Validation::class)
        ->assertSet('currentRequest', null)
        ->assertSet('pendingFamiliesCount', 0)
        ->assertSet('pendingChildrenCount', 0)
        ->assertDontSee('Alice');
});

test('gift validation does not list validated families without pending children', function () {
    $this->giftRequest->update(['family_number' => 1, 'status' => GiftRequest::STATUS_VALIDATED]);
    ($this->makeChild)($this->giftRequest, 'Alice', Child::STATUS_VALIDATED);

    $this->actingAs($this->admin);

    Livewire::test(Validation::class)
        ->assertSet('currentRequest', null)
        ->assertSet('pendingFamiliesCount', 0);
});

test('gift validation has no family decision', function () {
    $this->giftRequest->update(['family_number' => 1, 'status' => GiftRequest::STATUS_VALIDATED]);
    ($this->makeChild)($this->giftRequest, 'Alice');

    $this->actingAs($this->admin);

    Livewire::test(Validation::class)
        ->assertSet('currentRequest.id', $this->giftRequest->id)
        ->assertSet('pendingFamiliesCount', 1)
        ->assertSet('pendingChildrenCount', 1)
        ->assertDontSee('Décision pour la famille')
        ->assertDontSee('Refuser définitivement');
});

test('validateChild assigns child number and code', function () {
    $this->giftRequest->update(['family_number' => 3, 'status' => GiftRequest::STATUS_VALIDATED]);
    $child = ($this->makeChild)($this->giftRequest, 'Alice');

    $this->actingAs($this->admin);

    Livewire::test(Validation::class)
        ->set("childDecisions.{$child->id}", 'validated')
        ->call('submitValidation');

    $child->refresh();
    $this->giftRequest->refresh();
    expect($child->child_number)->toBe(1);
    expect($child->code)->toBe('Y0003/1');
    expect($child->status)->toBe(Child::STATUS_VALIDATED);
    expect($child->validated_at)->not->toBeNull();
    expect($this->giftRequest->status)->toBe(GiftRequest::STATUS_VALIDATED);
});

test('validateChild assigns sequential child numbers within same family', function () {
    $this->giftRequest->update(['family_number' => 1, 'status' => GiftRequest::STATUS_VALIDATED]);
    $child1 = ($this->makeChild)($this->giftRequest, 'Alice');
    $child2 = ($this->makeChild)($this->giftRequest, 'Bob');

    $this->actingAs($this->admin);

    Livewire::test(Validation::class)
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
    $child1 = ($this->makeChild)($this->giftRequest, 'Alice');
    $child2 = ($this->makeChild)($this->giftRequest, 'Bob');

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

    $child3 = ($this->makeChild)($giftRequest2, 'Charlie');

    $this->actingAs($this->admin);

    // Families are validated on the family validation page
    Livewire::test(FamilyValidation::class)
        ->call('validateFamily')
        ->call('validateFamily');

    // Then their children on the gift validation page
    Livewire::test(Validation::class)
        ->set("childDecisions.{$child1->id}", 'validated')
        ->set("childDecisions.{$child2->id}", 'validated')
        ->call('submitValidation')
        ->set("childDecisions.{$child3->id}", 'validated')
        ->call('submitValidation');

    $child1->refresh();
    $child2->refresh();
    $child3->refresh();

    expect($child1->code)->toBe('Y0001/1');
    expect($child2->code)->toBe('Y0001/2');
    expect($child3->code)->toBe('Y0002/1');

    $this->season->refresh();
    expect($this->season->next_family_number)->toBe(3);
});

test('gift validation only changes pending children', function () {
    $this->giftRequest->update(['family_number' => 1, 'status' => GiftRequest::STATUS_VALIDATED]);
    $printed = ($this->makeChild)($this->giftRequest, 'Alice', Child::STATUS_PRINTED);
    $pending = ($this->makeChild)($this->giftRequest, 'Bob');

    $this->actingAs($this->admin);

    Livewire::test(Validation::class)
        ->assertSet('childDecisions', [$pending->id => 'pending'])
        ->set("childDecisions.{$pending->id}", 'validated')
        ->call('submitValidation');

    expect($printed->fresh()->status)->toBe(Child::STATUS_PRINTED);
    expect($pending->fresh()->status)->toBe(Child::STATUS_VALIDATED);
});

test('gift validation ignores a request sent back to pending in the meantime', function () {
    $this->giftRequest->update(['family_number' => 1, 'status' => GiftRequest::STATUS_VALIDATED]);
    $child = ($this->makeChild)($this->giftRequest, 'Alice');

    $this->actingAs($this->admin);

    $component = Livewire::test(Validation::class)
        ->set("childDecisions.{$child->id}", 'validated');

    // The family edits its request: back to the family validation queue
    $this->giftRequest->setStatus(GiftRequest::STATUS_PENDING);

    $component->call('submitValidation');

    expect($child->fresh()->status)->toBe(Child::STATUS_PENDING);
    expect($child->fresh()->code)->toBeNull();
});

test('child correction keeps the family validated and sends a correction email', function () {
    Mail::fake();

    $this->giftRequest->update(['family_number' => 1, 'status' => GiftRequest::STATUS_VALIDATED]);
    $child = ($this->makeChild)($this->giftRequest, 'Alice');

    $this->actingAs($this->admin);

    Livewire::test(Validation::class)
        ->set("childDecisions.{$child->id}", 'correction')
        ->set("childComments.{$child->id}", 'Merci de préciser le cadeau.')
        ->call('submitValidation');

    expect($child->fresh()->status)->toBe(Child::STATUS_REJECTED);
    expect($this->giftRequest->fresh()->status)->toBe(GiftRequest::STATUS_VALIDATED);
    Mail::assertQueued(CorrectionRequestMail::class);
});

test('final family rejection in family validation final-rejects all children', function () {
    Mail::fake();

    $childPending = Child::create([
        'gift_request_id' => $this->giftRequest->id,
        'first_name' => 'Alice',
        'gender' => Child::GENDER_GIRL,
        'birth_year' => 2018,
        'gift' => 'Poupée',
        'status' => Child::STATUS_PENDING,
    ]);

    $childValidated = Child::create([
        'gift_request_id' => $this->giftRequest->id,
        'first_name' => 'Bob',
        'gender' => Child::GENDER_BOY,
        'birth_year' => 2016,
        'gift' => 'Lego',
        'status' => Child::STATUS_VALIDATED,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(FamilyValidation::class)
        ->call('openRejectionModal', true)
        ->set('rejectionComment', 'Refus définitif de la famille pour dossier incomplet.')
        ->call('confirmRejection');

    $this->giftRequest->refresh();
    $childPending->refresh();
    $childValidated->refresh();

    expect($this->giftRequest->status)->toBe(GiftRequest::STATUS_REJECTED_FINAL);
    expect($childPending->status)->toBe(Child::STATUS_REJECTED_FINAL);
    expect($childValidated->status)->toBe(Child::STATUS_REJECTED_FINAL);
    expect($childPending->rejection_comment)->toBe('Refus définitif de la famille pour dossier incomplet.');
    expect($childValidated->rejection_comment)->toBe('Refus définitif de la famille pour dossier incomplet.');
});

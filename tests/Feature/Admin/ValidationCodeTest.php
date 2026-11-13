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

test('validateFamily does not assign a family number', function () {
    $this->actingAs($this->admin);

    Livewire::test(FamilyValidation::class)->call('validateFamily');

    $this->giftRequest->refresh();
    expect($this->giftRequest->family_number)->toBeNull();
    expect($this->giftRequest->status)->toBe(GiftRequest::STATUS_VALIDATED);
});

test('validateFamily keeps a family number already assigned', function () {
    $this->giftRequest->update(['family_number' => 42]);

    $this->actingAs($this->admin);

    Livewire::test(FamilyValidation::class)->call('validateFamily');

    expect($this->giftRequest->fresh()->family_number)->toBe(42);
});

test('gift validation does not list requests of families still pending', function () {
    ($this->makeChild)($this->giftRequest, 'Alice');

    $this->actingAs($this->admin);

    Livewire::test(Validation::class)
        ->assertSet('currentRequest', null)
        ->assertSet('queuedFamiliesCount', 0)
        ->assertSet('pendingChildrenCount', 0)
        ->assertDontSee('Alice');
});

test('gift validation does not list validated families without pending children', function () {
    $this->giftRequest->update(['family_number' => 1, 'status' => GiftRequest::STATUS_VALIDATED]);
    ($this->makeChild)($this->giftRequest, 'Alice', Child::STATUS_VALIDATED);

    $this->actingAs($this->admin);

    Livewire::test(Validation::class)
        ->assertSet('currentRequest', null)
        ->assertSet('queuedFamiliesCount', 0);
});

test('gift validation has no family decision', function () {
    $this->giftRequest->update(['family_number' => 1, 'status' => GiftRequest::STATUS_VALIDATED]);
    ($this->makeChild)($this->giftRequest, 'Alice');

    $this->actingAs($this->admin);

    Livewire::test(Validation::class)
        ->assertSet('currentRequest.id', $this->giftRequest->id)
        ->assertSet('queuedFamiliesCount', 1)
        ->assertSet('pendingChildrenCount', 1)
        ->assertDontSee('Décision pour la famille')
        ->assertDontSee('Refuser définitivement');
});

test('validating a child does not assign its number nor its code', function () {
    $this->giftRequest->update(['status' => GiftRequest::STATUS_VALIDATED]);
    $child = ($this->makeChild)($this->giftRequest, 'Alice');

    $this->actingAs($this->admin);

    Livewire::test(Validation::class)
        ->set("childDecisions.{$child->id}", 'validated')
        ->call('submitValidation');

    $child->refresh();
    expect($child->status)->toBe(Child::STATUS_VALIDATED);
    expect($child->validated_at)->not->toBeNull();
    expect($child->child_number)->toBeNull();
    expect($child->code)->toBeNull();
    expect($this->giftRequest->fresh()->family_number)->toBeNull();
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

    Mail::fake();

    $component->call('submitValidation')
        ->assertSee("vos décisions n'ont pas été enregistrées");

    expect($child->fresh()->status)->toBe(Child::STATUS_PENDING);
    expect($child->fresh()->code)->toBeNull();
    Mail::assertNothingQueued();
});

test('gift validation shows the real status badge of already decided children', function () {
    $this->giftRequest->update(['family_number' => 1, 'status' => GiftRequest::STATUS_VALIDATED]);
    ($this->makeChild)($this->giftRequest, 'Alice', Child::STATUS_REJECTED_FINAL);
    ($this->makeChild)($this->giftRequest, 'Bob');

    $this->actingAs($this->admin);

    Livewire::test(Validation::class)
        ->assertSeeHtml('<span class="badge--rejected">')
        ->assertSeeHtml('<span class="badge--pending">');
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

test('a correction can be asked for one child while the other children stay pending', function () {
    Mail::fake();

    $this->giftRequest->update(['family_number' => 1, 'status' => GiftRequest::STATUS_VALIDATED]);
    $corrected = ($this->makeChild)($this->giftRequest, 'Alice');
    $waiting = ($this->makeChild)($this->giftRequest, 'Bob');

    $this->actingAs($this->admin);

    Livewire::test(Validation::class)
        ->set("childDecisions.{$corrected->id}", 'correction')
        ->set("childComments.{$corrected->id}", 'Merci de préciser le cadeau.')
        ->call('submitValidation');

    expect($corrected->fresh()->status)->toBe(Child::STATUS_REJECTED);
    expect($waiting->fresh()->status)->toBe(Child::STATUS_PENDING);
    expect($this->giftRequest->fresh()->status)->toBe(GiftRequest::STATUS_VALIDATED);
    Mail::assertQueued(CorrectionRequestMail::class);

    // The family stays in the gift validation queue for the child still pending
    Livewire::test(Validation::class)
        ->assertSet('currentRequest.id', $this->giftRequest->id)
        ->assertSet('childDecisions', [$waiting->id => 'pending']);
});

test('gift validation cannot be submitted without any decision', function () {
    $this->giftRequest->update(['family_number' => 1, 'status' => GiftRequest::STATUS_VALIDATED]);
    $child = ($this->makeChild)($this->giftRequest, 'Alice');

    $this->actingAs($this->admin);

    Livewire::test(Validation::class)
        ->call('submitValidation');

    expect($child->fresh()->status)->toBe(Child::STATUS_PENDING);
});

test('a family partially decided goes to the end of the gift validation queue', function () {
    $this->giftRequest->update(['family_number' => 1, 'status' => GiftRequest::STATUS_VALIDATED]);
    $decided = ($this->makeChild)($this->giftRequest, 'Alice');
    ($this->makeChild)($this->giftRequest, 'Bob');

    $otherFamily = Family::create([
        'email' => 'other@example.com',
        'first_name' => 'Marie',
        'last_name' => 'Martin',
        'street_name' => 'Rue du Lac',
        'house_no' => '5',
        'postal_code' => '1000',
        'city' => 'Lausanne',
        'phone' => '0799999999',
    ]);
    $otherRequest = GiftRequest::create([
        'family_id' => $otherFamily->id,
        'season_id' => $this->season->id,
        'status' => GiftRequest::STATUS_VALIDATED,
        'family_number' => 2,
    ]);
    ($this->makeChild)($otherRequest, 'Chloé');

    GiftRequest::whereKey($this->giftRequest->id)->toBase()->update(['updated_at' => now()->subHour()]);
    GiftRequest::whereKey($otherRequest->id)->toBase()->update(['updated_at' => now()->subMinutes(30)]);

    $this->actingAs($this->admin);

    Livewire::test(Validation::class)
        ->assertSet('currentRequest.id', $this->giftRequest->id)
        ->set("childDecisions.{$decided->id}", 'validated')
        ->call('submitValidation')
        ->assertSet('currentRequest.id', $otherRequest->id);
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

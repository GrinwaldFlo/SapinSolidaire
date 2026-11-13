<?php

use App\Livewire\Admin\FamilyManagement;
use App\Livewire\Admin\FamilyValidation;
use App\Livewire\Admin\GiftDelivery;
use App\Livewire\Admin\GiftReception;
use App\Livewire\Family\GiftRequestForm;
use App\Models\Child;
use App\Models\EmailToken;
use App\Models\Family;
use App\Models\GiftRequest;
use App\Models\Role;
use App\Models\Season;
use App\Models\User;
use App\Services\AddressValidationService;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

/**
 * Once its gift card is printed, a child's status can no longer be decided
 * (only the reception / delivery lifecycle goes on) and the family cannot change it.
 */
beforeEach(function () {
    Role::firstOrCreate(['name' => Role::ADMIN]);
    $this->admin = User::factory()->create();
    $this->admin->assignRole(Role::ADMIN);

    $this->season = Season::create([
        'name' => 'Noël 2026',
        'start_date' => now()->subDays(10),
        'end_date' => now()->addDays(20),
        'modification_deadline' => now()->addDays(20),
        'next_family_number' => 2,
    ]);

    $this->createFamilyRequest = function (string $email, string $status): GiftRequest {
        $family = Family::create([
            'email' => $email,
            'first_name' => 'Paul',
            'last_name' => 'Martin',
            'street_name' => 'Rue de Test',
            'house_no' => '1',
            'postal_code' => '1000',
            'city' => 'Lausanne',
            'phone' => '+41791234567',
        ]);

        return GiftRequest::create([
            'family_id' => $family->id,
            'season_id' => $this->season->id,
            'family_number' => 1,
            'status' => $status,
        ]);
    };

    $this->makeChild = fn (GiftRequest $request, string $firstName, string $status) => Child::create([
        'gift_request_id' => $request->id,
        'first_name' => $firstName,
        'gender' => Child::GENDER_GIRL,
        'birth_year' => 2018,
        'gift' => 'Livre',
        'status' => $status,
    ]);

    $this->giftRequest = ($this->createFamilyRequest)('famille@example.com', GiftRequest::STATUS_PENDING);
    $this->printed = ($this->makeChild)($this->giftRequest, 'Emma', Child::STATUS_PRINTED);
    $this->received = ($this->makeChild)($this->giftRequest, 'Léa', Child::STATUS_RECEIVED);
    $this->given = ($this->makeChild)($this->giftRequest, 'Zoé', Child::STATUS_GIVEN);
    $this->notPrinted = ($this->makeChild)($this->giftRequest, 'Léo', Child::STATUS_VALIDATED);

    $this->expectPrintedCardsUntouched = function (): void {
        expect($this->printed->fresh()->status)->toBe(Child::STATUS_PRINTED);
        expect($this->received->fresh()->status)->toBe(Child::STATUS_RECEIVED);
        expect($this->given->fresh()->status)->toBe(Child::STATUS_GIVEN);
    };
});

test('final family rejection keeps printed children', function () {
    Mail::fake();
    $this->actingAs($this->admin);

    Livewire::test(FamilyValidation::class)
        ->call('openRejectionModal', true)
        ->set('rejectionComment', 'Refus définitif de la famille pour dossier incomplet.')
        ->call('confirmRejection');

    ($this->expectPrintedCardsUntouched)();
    expect($this->notPrinted->fresh()->status)->toBe(Child::STATUS_REJECTED_FINAL);
});

test('resetting a family to pending keeps printed children', function () {
    $this->actingAs($this->admin);

    Livewire::test(FamilyManagement::class)
        ->call('applyAction', $this->giftRequest->family_id, 'reset_pending');

    ($this->expectPrintedCardsUntouched)();
    expect($this->notPrinted->fresh()->status)->toBe(Child::STATUS_PENDING);
});

test('excluding a family keeps printed children', function () {
    $this->actingAs($this->admin);

    Livewire::test(FamilyManagement::class)
        ->set('exclusionReasons.'.$this->giftRequest->family_id, 'Fraude avérée')
        ->call('applyAction', $this->giftRequest->family_id, 'exclude');

    ($this->expectPrintedCardsUntouched)();
    expect($this->notPrinted->fresh()->status)->toBe(Child::STATUS_REJECTED_FINAL);
});

test('reception only marks printed children as received', function () {
    $this->actingAs($this->admin);

    Livewire::test(GiftReception::class)
        ->call('markAsReceived', $this->notPrinted->id)
        ->call('markAsReceived', $this->given->id)
        ->call('markAsReceived', $this->printed->id);

    expect($this->notPrinted->fresh()->status)->toBe(Child::STATUS_VALIDATED);
    expect($this->given->fresh()->status)->toBe(Child::STATUS_GIVEN);
    expect($this->printed->fresh()->status)->toBe(Child::STATUS_RECEIVED);
});

test('delivery only marks received children as given', function () {
    $this->actingAs($this->admin);

    Livewire::test(GiftDelivery::class)
        ->call('markAsGiven', $this->notPrinted->id)
        ->call('markAsGiven', $this->printed->id)
        ->call('markAsGiven', $this->received->id);

    expect($this->notPrinted->fresh()->status)->toBe(Child::STATUS_VALIDATED);
    expect($this->printed->fresh()->status)->toBe(Child::STATUS_PRINTED);
    expect($this->received->fresh()->status)->toBe(Child::STATUS_GIVEN);
});

test('the family cannot change or remove a printed child', function () {
    $this->mock(AddressValidationService::class)
        ->shouldReceive('validate')
        ->andReturn(['Valide' => true, 'Message' => '', 'FormatedAddress' => []]);

    $token = EmailToken::createForEmail('famille@example.com');
    $component = Livewire::test(GiftRequestForm::class, ['token' => $token->token]);

    // Data forged in the browser for the printed children, then one of them removed
    $children = collect($component->get('children'))
        ->map(fn (array $child) => $child['id'] === $this->printed->id ? [...$child, 'gift' => 'Console'] : $child)
        ->reject(fn (array $child) => $child['id'] === $this->given->id)
        ->values()
        ->all();

    $component->set('children', $children)->call('submit');

    ($this->expectPrintedCardsUntouched)();
    expect($this->printed->fresh()->gift)->toBe('Livre');
    expect(Child::find($this->given->id))->not->toBeNull();
});

test('the family cannot change a child of another family', function () {
    $this->mock(AddressValidationService::class)
        ->shouldReceive('validate')
        ->andReturn(['Valide' => true, 'Message' => '', 'FormatedAddress' => []]);

    $otherRequest = ($this->createFamilyRequest)('autre@example.com', GiftRequest::STATUS_VALIDATED);
    $otherChild = ($this->makeChild)($otherRequest, 'Hugo', Child::STATUS_VALIDATED);

    $token = EmailToken::createForEmail('famille@example.com');
    $component = Livewire::test(GiftRequestForm::class, ['token' => $token->token]);

    $children = collect($component->get('children'))
        ->map(fn (array $child) => $child['id'] === $this->notPrinted->id
            ? [...$child, 'id' => $otherChild->id, 'gift' => 'Console']
            : $child)
        ->all();

    $component->set('children', $children)->call('submit');

    expect($otherChild->fresh()->gift)->toBe('Livre');
    expect($otherChild->fresh()->status)->toBe(Child::STATUS_VALIDATED);
});

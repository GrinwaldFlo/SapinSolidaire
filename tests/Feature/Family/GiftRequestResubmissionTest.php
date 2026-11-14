<?php

use App\Livewire\Family\GiftRequestForm;
use App\Models\Child;
use App\Models\EmailToken;
use App\Models\Family;
use App\Models\GiftRequest;
use App\Models\Season;
use App\Services\AddressValidationService;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    $this->season = Season::create([
        'name' => 'Noël 2026',
        'start_date' => now()->subDay(),
        'end_date' => now()->addMonth(),
        'modification_deadline' => now()->addMonth(),
    ]);

    $this->family = Family::create([
        'email' => 'famille@example.com',
        'first_name' => 'Paul',
        'last_name' => 'Martin',
        'street_name' => 'Rue de Test',
        'house_no' => '1',
        'postal_code' => '1000',
        'city' => 'Lausanne',
        'phone' => '+41791234567',
    ]);

    $this->giftRequest = GiftRequest::create([
        'family_id' => $this->family->id,
        'season_id' => $this->season->id,
        'family_number' => 1,
        'status' => GiftRequest::STATUS_VALIDATED,
    ]);

    $this->makeChild = fn (string $firstName, string $status, ?string $code = null) => Child::create([
        'gift_request_id' => $this->giftRequest->id,
        'first_name' => $firstName,
        'gender' => Child::GENDER_GIRL,
        'birth_year' => 2018,
        'gift' => 'Livre',
        'status' => $status,
        'code' => $code,
        'validated_at' => $status === Child::STATUS_VALIDATED ? now()->subDay() : null,
    ]);

    $this->validatedChild = ($this->makeChild)('Emma', Child::STATUS_VALIDATED, '001/1');
    $this->correctedChild = ($this->makeChild)('Léo', Child::STATUS_REJECTED);

    $this->token = EmailToken::createForEmail('famille@example.com');

    $this->mock(AddressValidationService::class)
        ->shouldReceive('validate')
        ->andReturn([
            'Valide' => true,
            'Message' => '',
            'FormatedAddress' => [],
        ]);
});

/** Index of a child in the form children list. */
function formChildIndex(Testable $component, Child $child): int
{
    return collect($component->get('children'))->search(fn (array $data) => $data['id'] === $child->id);
}

function openFamilyForm(EmailToken $token): Testable
{
    return Livewire::test(GiftRequestForm::class, ['token' => $token->token]);
}

test('correcting one child keeps the family validated and the other child validated', function () {
    $component = openFamilyForm($this->token);
    $component->set('children.'.formChildIndex($component, $this->correctedChild).'.gift', 'Puzzle')
        ->call('submit')
        ->assertSet('submitted', true);

    expect($this->giftRequest->fresh()->status)->toBe(GiftRequest::STATUS_VALIDATED);
    expect($this->validatedChild->fresh()->status)->toBe(Child::STATUS_VALIDATED);
    expect($this->validatedChild->fresh()->code)->toBe('001/1');
    expect($this->correctedChild->fresh()->status)->toBe(Child::STATUS_PENDING);
    expect($this->correctedChild->fresh()->gift)->toBe('Puzzle');
});

test('a corrected child sent back unchanged goes back to pending', function () {
    openFamilyForm($this->token)->call('submit');

    expect($this->correctedChild->fresh()->status)->toBe(Child::STATUS_PENDING);
    expect($this->validatedChild->fresh()->status)->toBe(Child::STATUS_VALIDATED);
    expect($this->giftRequest->fresh()->status)->toBe(GiftRequest::STATUS_VALIDATED);
});

test('changing a validated child sends it back to validation', function () {
    $component = openFamilyForm($this->token);
    $component->set('children.'.formChildIndex($component, $this->validatedChild).'.gift', 'Puzzle')
        ->call('submit');

    expect($this->validatedChild->fresh()->status)->toBe(Child::STATUS_PENDING);
    expect($this->validatedChild->fresh()->code)->toBe('001/1');
    expect($this->giftRequest->fresh()->status)->toBe(GiftRequest::STATUS_VALIDATED);
});

test('changing family data sends the family back to family validation only', function () {
    openFamilyForm($this->token)
        ->set('phone', '+41797654321')
        ->call('submit');

    expect($this->giftRequest->fresh()->status)->toBe(GiftRequest::STATUS_PENDING);
    expect($this->validatedChild->fresh()->status)->toBe(Child::STATUS_VALIDATED);
});

test('adding a child keeps the family validated', function () {
    $component = openFamilyForm($this->token)->call('addChild');
    $index = count($component->get('children')) - 1;

    $component
        ->set("children.{$index}.first_name", 'Zoé')
        ->set("children.{$index}.gender", Child::GENDER_GIRL)
        ->set("children.{$index}.birth_year", '2019')
        ->set("children.{$index}.gift", 'Livre')
        ->call('submit');

    expect($this->giftRequest->fresh()->status)->toBe(GiftRequest::STATUS_VALIDATED);
    expect($this->giftRequest->children()->where('first_name', 'Zoé')->value('status'))->toBe(Child::STATUS_PENDING);
    expect($this->validatedChild->fresh()->status)->toBe(Child::STATUS_VALIDATED);
});

test('a family asked for a correction goes back to family validation even without change', function () {
    $this->giftRequest->setStatus(GiftRequest::STATUS_REJECTED, 'Merci de corriger.');

    openFamilyForm($this->token)->call('submit');

    expect($this->giftRequest->fresh()->status)->toBe(GiftRequest::STATUS_PENDING);
    expect($this->validatedChild->fresh()->status)->toBe(Child::STATUS_VALIDATED);
});

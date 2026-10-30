<?php

use App\Livewire\Family\GiftRequestForm;
use App\Models\Child;
use App\Models\EmailToken;
use App\Models\Family;
use App\Models\GiftRequest;
use App\Models\Season;
use App\Services\AddressValidationService;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    $this->season = Season::create([
        'name' => 'Noël 2026',
        'start_date' => now()->subDay(),
        'end_date' => now()->addMonth(),
        'modification_deadline' => now()->addMonth(),
        'next_family_number' => 1,
    ]);

    $this->victim = Family::create([
        'email' => 'victim@example.com',
        'first_name' => 'Victim',
        'last_name' => 'Family',
        'street_name' => 'Rue',
        'house_no' => '1',
        'postal_code' => '1000',
        'city' => 'Lausanne',
        'phone' => '+41791234567',
    ]);

    $request = GiftRequest::create([
        'family_id' => $this->victim->id,
        'season_id' => $this->season->id,
        'status' => GiftRequest::STATUS_PENDING,
    ]);

    $this->victimChild = Child::create([
        'gift_request_id' => $request->id,
        'first_name' => 'Lea',
        'gender' => Child::GENDER_GIRL,
        'birth_year' => 2018,
        'gift' => 'Livre',
    ]);

    $this->attackerToken = EmailToken::createForEmail('attacker@example.com');

    $this->mock(AddressValidationService::class)
        ->shouldReceive('validate')
        ->andReturn([
            'Valide' => true,
            'Message' => '',
            'FormatedAddress' => [],
        ]);
});

/** Fill a form that passes every validation, then submit it. */
function submitCompleteFamilyForm(Testable $component): void
{
    $component
        ->call('acceptConsecutiveYears')
        ->call('acceptPickupCommitment')
        ->call('acceptCity')
        ->set('firstName', 'Paul')
        ->set('lastName', 'Martin')
        ->set('streetName', 'Rue de Test')
        ->set('houseNo', '1')
        ->set('postalCode', '1000')
        ->set('city', 'Lausanne')
        ->set('phone', '+41791234567')
        ->set('children.0.first_name', 'Emma')
        ->set('children.0.gender', Child::GENDER_GIRL)
        ->set('children.0.birth_year', '2018')
        ->set('children.0.gift', 'Livre')
        ->call('submit');
}

test('the family email cannot be changed from the browser', function () {
    Livewire::test(GiftRequestForm::class, ['token' => $this->attackerToken->token])
        ->set('email', 'victim@example.com');
})->throws(CannotUpdateLockedPropertyException::class);

test('the access token cannot be changed from the browser', function () {
    Livewire::test(GiftRequestForm::class, ['token' => $this->attackerToken->token])
        ->set('token', 'another-token');
})->throws(CannotUpdateLockedPropertyException::class);

test('another family data is left untouched after a tampering attempt', function () {
    try {
        Livewire::test(GiftRequestForm::class, ['token' => $this->attackerToken->token])
            ->set('email', 'victim@example.com')
            ->call('submit');
    } catch (CannotUpdateLockedPropertyException) {
        // expected
    }

    expect($this->victim->fresh()->first_name)->toBe('Victim');
    expect(Child::find($this->victimChild->id))->not->toBeNull();
});

test('the token validity cannot be changed from the browser', function () {
    Livewire::test(GiftRequestForm::class, ['token' => 'invalid-token'])
        ->set('tokenValid', true);
})->throws(CannotUpdateLockedPropertyException::class);

test('the existing proof path cannot be changed from the browser', function () {
    Livewire::test(GiftRequestForm::class, ['token' => $this->attackerToken->token])
        ->set('existingProofPath', 'proof-of-habitation/fake.jpg');
})->throws(CannotUpdateLockedPropertyException::class);

test('the proof requirement cannot be disabled from the browser', function () {
    Livewire::test(GiftRequestForm::class, ['token' => $this->attackerToken->token])
        ->set('proofOfHabitationEnabled', false);
})->throws(CannotUpdateLockedPropertyException::class);

test('submit does nothing with an invalid token', function () {
    $familyCount = Family::count();

    submitCompleteFamilyForm(Livewire::test(GiftRequestForm::class, ['token' => 'invalid-token']));

    expect(Family::count())->toBe($familyCount);
    expect(GiftRequest::count())->toBe(1);
});

test('submit does nothing when no season is active', function () {
    $this->season->update(['start_date' => now()->addWeek(), 'end_date' => now()->addMonth()]);
    $familyCount = Family::count();

    $component = Livewire::test(GiftRequestForm::class, ['token' => $this->attackerToken->token])
        ->assertSet('tokenValid', false);
    submitCompleteFamilyForm($component);

    expect(Family::count())->toBe($familyCount);
    expect(Family::where('email', 'attacker@example.com')->exists())->toBeFalse();
});

test('submit still works with a valid token', function () {
    submitCompleteFamilyForm(Livewire::test(GiftRequestForm::class, ['token' => $this->attackerToken->token]));

    expect(Family::where('email', 'attacker@example.com')->exists())->toBeTrue();
});

<?php

use App\Livewire\Family\GiftRequestForm;
use App\Models\Child;
use App\Models\EmailToken;
use App\Models\Family;
use App\Models\GiftRequest;
use App\Models\Season;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
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
});

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

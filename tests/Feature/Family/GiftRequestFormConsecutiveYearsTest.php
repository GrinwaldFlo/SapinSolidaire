<?php

use App\Livewire\Family\GiftRequestForm;
use App\Models\EmailToken;
use App\Models\Family;
use App\Models\GiftRequest;
use App\Models\Season;
use App\Models\Setting;
use Livewire\Livewire;

beforeEach(function () {
    Setting::clearCache();
    Setting::setValue(Setting::MAX_CONSECUTIVE_YEARS, 3);

    $this->season = Season::create([
        'name' => 'Active Season',
        'start_date' => now()->subDay(),
        'end_date' => now()->addMonth(),
        'modification_deadline' => now()->addMonth(),
    ]);

    $this->family = Family::create([
        'email' => 'loyal@example.com',
        'first_name' => 'Jean',
        'last_name' => 'Dupont',
        'street_name' => 'Rue du Lac',
        'house_no' => '1',
        'postal_code' => '1400',
        'city' => 'Yverdon-les-Bains',
        'phone' => '0791234567',
    ]);

    $this->emailToken = EmailToken::createForEmail('loyal@example.com');

    $this->createPastRequest = function (int $yearsAgo, string $status = GiftRequest::STATUS_VALIDATED): GiftRequest {
        $season = Season::create([
            'name' => "Season -{$yearsAgo}",
            'start_date' => now()->subYears($yearsAgo)->subDay(),
            'end_date' => now()->subYears($yearsAgo)->addMonth(),
        ]);

        return GiftRequest::create([
            'family_id' => $this->family->id,
            'season_id' => $season->id,
            'status' => $status,
        ]);
    };
});

test('family with requests in the previous years up to the limit is blocked', function () {
    ($this->createPastRequest)(1);
    ($this->createPastRequest)(2);

    Livewire::test(GiftRequestForm::class, ['token' => $this->emailToken->token])
        ->assertSet('consecutiveYearsLimitReached', true)
        ->assertSee('Pas de demande cette année')
        ->assertSee('Vous pourrez faire une nouvelle demande')
        ->assertSee('en '.now()->subYears(2)->year.' et en '.now()->subYear()->year);
});

test('family below the limit can request', function () {
    ($this->createPastRequest)(1);

    Livewire::test(GiftRequestForm::class, ['token' => $this->emailToken->token])
        ->assertSet('consecutiveYearsLimitReached', false);
});

test('a gap year resets the consecutive count', function () {
    ($this->createPastRequest)(1);
    ($this->createPastRequest)(3);

    Livewire::test(GiftRequestForm::class, ['token' => $this->emailToken->token])
        ->assertSet('consecutiveYearsLimitReached', false);
});

test('rejected requests do not count as a year', function () {
    ($this->createPastRequest)(1);
    ($this->createPastRequest)(2, GiftRequest::STATUS_REJECTED);

    Livewire::test(GiftRequestForm::class, ['token' => $this->emailToken->token])
        ->assertSet('consecutiveYearsLimitReached', false);
});

test('limit follows the max consecutive years setting', function () {
    Setting::setValue(Setting::MAX_CONSECUTIVE_YEARS, 4);
    ($this->createPastRequest)(1);
    ($this->createPastRequest)(2);

    Livewire::test(GiftRequestForm::class, ['token' => $this->emailToken->token])
        ->assertSet('consecutiveYearsLimitReached', false);
});

test('existing request for the active season stays editable', function () {
    ($this->createPastRequest)(1);
    ($this->createPastRequest)(2);

    GiftRequest::create([
        'family_id' => $this->family->id,
        'season_id' => $this->season->id,
        'status' => GiftRequest::STATUS_PENDING,
    ]);

    Livewire::test(GiftRequestForm::class, ['token' => $this->emailToken->token])
        ->assertSet('consecutiveYearsLimitReached', false)
        ->assertSet('isModifying', true);
});

test('submit is refused when the limit is reached', function () {
    $component = Livewire::test(GiftRequestForm::class, ['token' => $this->emailToken->token])
        ->assertSet('consecutiveYearsLimitReached', false);

    ($this->createPastRequest)(1);
    ($this->createPastRequest)(2);

    $component->call('submit')
        ->assertSet('consecutiveYearsLimitReached', true);

    expect($this->family->getRequestForSeason($this->season))->toBeNull();
});

test('consecutive years limit flag cannot be changed from the client', function () {
    ($this->createPastRequest)(1);
    ($this->createPastRequest)(2);

    Livewire::test(GiftRequestForm::class, ['token' => $this->emailToken->token])
        ->set('consecutiveYearsLimitReached', false);
})->throws(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);

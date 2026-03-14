<?php

use App\Livewire\Family\GiftRequestForm;
use App\Models\EmailToken;
use App\Models\Season;
use App\Models\Setting;
use Livewire\Livewire;

beforeEach(function () {
    Setting::clearCache();

    // Create an active season
    $this->season = Season::create([
        'name' => 'Test Season',
        'start_date' => now()->subDay(),
        'end_date' => now()->addMonth(),
        'modification_deadline' => now()->addMonth(),
    ]);

    // Create a valid email token
    $this->emailToken = EmailToken::createForEmail('test@example.com');
});

// --- acceptCity tests ---

test('acceptCity without selection shows error when cities are configured', function () {
    Setting::setValue(Setting::ALLOWED_CITIES, 'Lausanne, Morges');

    Livewire::test(GiftRequestForm::class, ['token' => $this->emailToken->token])
        ->call('acceptConsecutiveYears')
        ->set('selectedCity', '')
        ->call('acceptCity')
        ->assertHasErrors('selectedCity')
        ->assertSet('cityAccepted', false)
        ->assertSet('step', 1);
});

test('acceptCity with valid selection sets city and advances', function () {
    Setting::setValue(Setting::ALLOWED_CITIES, 'Lausanne, Morges');

    Livewire::test(GiftRequestForm::class, ['token' => $this->emailToken->token])
        ->call('acceptConsecutiveYears')
        ->set('selectedCity', 'Lausanne')
        ->call('acceptCity')
        ->assertHasNoErrors()
        ->assertSet('cityAccepted', true)
        ->assertSet('city', 'Lausanne')
        ->assertSet('step', 2);
});

test('acceptCity without cities configured allows acceptance without selection', function () {
    // No allowed cities configured
    Livewire::test(GiftRequestForm::class, ['token' => $this->emailToken->token])
        ->call('acceptConsecutiveYears')
        ->call('acceptCity')
        ->assertHasNoErrors()
        ->assertSet('cityAccepted', true)
        ->assertSet('step', 2);
});

test('acceptCity does not advance to step 2 without consecutive years accepted', function () {
    Setting::setValue(Setting::ALLOWED_CITIES, 'Lausanne, Morges');

    Livewire::test(GiftRequestForm::class, ['token' => $this->emailToken->token])
        ->set('selectedCity', 'Lausanne')
        ->call('acceptCity')
        ->assertSet('cityAccepted', true)
        ->assertSet('city', 'Lausanne')
        ->assertSet('step', 1);
});

test('selected city pre-fills the city field in the form', function () {
    Setting::setValue(Setting::ALLOWED_CITIES, 'Lausanne, Morges, Renens');

    $component = Livewire::test(GiftRequestForm::class, ['token' => $this->emailToken->token])
        ->call('acceptConsecutiveYears')
        ->set('selectedCity', 'Renens')
        ->call('acceptCity');

    $component->assertSet('city', 'Renens');
    $component->assertSet('step', 2);
});

// --- Eligibility step rendering ---

test('allowedCities are loaded from settings on mount', function () {
    Setting::setValue(Setting::ALLOWED_CITIES, 'Lausanne, Morges');

    $component = Livewire::test(GiftRequestForm::class, ['token' => $this->emailToken->token]);

    $component->assertSet('allowedCities', ['Lausanne', 'Morges']);
});

test('allowedCities is empty when no setting exists', function () {
    $component = Livewire::test(GiftRequestForm::class, ['token' => $this->emailToken->token]);

    $component->assertSet('allowedCities', []);
});

// --- Submit validation with city ---

test('submit rejects non-allowed city', function () {
    Setting::setValue(Setting::ALLOWED_CITIES, 'Lausanne, Morges');

    Livewire::test(GiftRequestForm::class, ['token' => $this->emailToken->token])
        ->call('acceptConsecutiveYears')
        ->set('selectedCity', 'Lausanne')
        ->call('acceptCity')
        ->set('city', 'Genève')
        ->set('firstName', 'Jean')
        ->set('lastName', 'Dupont')
        ->set('address', 'Rue de Test 1')
        ->set('postalCode', '1000')
        ->set('phone', '+41791234567')
        ->set('children.0.first_name', 'Petit')
        ->set('children.0.birth_year', '2018')
        ->set('children.0.gift', 'Livre')
        ->call('submit')
        ->assertHasErrors('city');
});

test('submit accepts allowed city', function () {
    Setting::setValue(Setting::ALLOWED_CITIES, 'Lausanne, Morges');

    Livewire::test(GiftRequestForm::class, ['token' => $this->emailToken->token])
        ->call('acceptConsecutiveYears')
        ->set('selectedCity', 'Lausanne')
        ->call('acceptCity')
        ->set('firstName', 'Jean')
        ->set('lastName', 'Dupont')
        ->set('address', 'Rue de Test 1')
        ->set('postalCode', '1000')
        ->set('phone', '+41791234567')
        ->set('children.0.first_name', 'Petit')
        ->set('children.0.birth_year', '2018')
        ->set('children.0.gift', 'Livre')
        ->call('submit')
        ->assertHasNoErrors('city');
});

test('submit allows any city when no cities are configured', function () {
    // No allowed cities configured

    Livewire::test(GiftRequestForm::class, ['token' => $this->emailToken->token])
        ->call('acceptConsecutiveYears')
        ->call('acceptCity')
        ->set('city', 'AnyCity')
        ->set('firstName', 'Jean')
        ->set('lastName', 'Dupont')
        ->set('address', 'Rue de Test 1')
        ->set('postalCode', '1000')
        ->set('phone', '+41791234567')
        ->set('children.0.first_name', 'Petit')
        ->set('children.0.birth_year', '2018')
        ->set('children.0.gift', 'Livre')
        ->call('submit')
        ->assertHasNoErrors('city');
});

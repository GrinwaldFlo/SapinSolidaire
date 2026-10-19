<?php

use App\Livewire\Family\GiftRequestForm;
use App\Livewire\Family\Home;
use App\Models\Child;
use App\Models\EmailToken;
use App\Models\Family;
use App\Models\GiftRequest;
use App\Models\Season;
use App\Models\Setting;
use Livewire\Livewire;

beforeEach(function () {
    Setting::clearCache();

    $this->season = Season::create([
        'name' => 'Active Season',
        'start_date' => now()->subDays(10),
        'end_date' => now()->addMonth(),
        'modification_deadline' => now()->addWeek(),
    ]);

    $this->family = Family::create([
        'email' => 'registered@example.com',
        'first_name' => 'Jean',
        'last_name' => 'Dupont',
        'street_name' => 'Rue du Lac',
        'house_no' => '1',
        'postal_code' => '1400',
        'city' => 'Yverdon-les-Bains',
        'phone' => '0791234567',
    ]);

    $this->giftRequest = GiftRequest::create([
        'family_id' => $this->family->id,
        'season_id' => $this->season->id,
        'status' => GiftRequest::STATUS_PENDING,
    ]);

    Child::create([
        'gift_request_id' => $this->giftRequest->id,
        'first_name' => 'Léa',
        'gender' => Child::GENDER_GIRL,
        'birth_year' => (int) date('Y') - 5,
        'gift' => 'Poupée',
    ]);

    $this->registeredToken = EmailToken::createForEmail('registered@example.com');
    $this->newToken = EmailToken::createForEmail('new@example.com');
});

function blockRegistrations(string $reason = ''): void
{
    Setting::setValue(Setting::REGISTRATION_BLOCKED, '1');
    Setting::setValue(Setting::REGISTRATION_BLOCKED_REASON, $reason);
}

function passModificationDeadline(Season $season): void
{
    $season->update(['modification_deadline' => now()->subDay()]);
}

test('registrations are open by default', function () {
    Livewire::test(GiftRequestForm::class, ['token' => $this->newToken->token])
        ->assertSet('canModify', true)
        ->assertSet('registrationClosedMessage', null);

    Livewire::test(Home::class)
        ->assertSet('registrationClosedMessage', null)
        ->assertDontSee('Inscriptions closes');
});

test('home page shows the block reason when registrations are blocked', function () {
    blockRegistrations("Trop de demandes.\nMerci de votre compréhension.");

    Livewire::test(Home::class)
        ->assertSet('registrationClosedMessage', "Trop de demandes.\nMerci de votre compréhension.")
        ->assertSee('Inscriptions closes')
        ->assertSee('Trop de demandes.')
        ->assertSee('Entrez votre adresse e-mail pour la consulter');
});

test('home page shows a default message when blocked without reason', function () {
    blockRegistrations();

    Livewire::test(Home::class)
        ->assertSee('Les inscriptions sont momentanément fermées.');
});

test('home page shows the closed message after the modification deadline', function () {
    passModificationDeadline($this->season);

    Livewire::test(Home::class)
        ->assertSee('Inscriptions closes')
        ->assertSee($this->season->fresh()->modification_deadline->format('d/m/Y'));
});

test('home page still sends an access link when registrations are closed', function () {
    blockRegistrations('Fermé');

    Livewire::test(Home::class)
        ->set('email', 'registered@example.com')
        ->call('sendLink')
        ->assertSet('emailSent', true);
});

test('new family cannot register when registrations are blocked', function () {
    blockRegistrations('Fermé pour cause de quota');

    Livewire::test(GiftRequestForm::class, ['token' => $this->newToken->token])
        ->assertSet('canModify', false)
        ->assertSet('registrationClosedMessage', 'Fermé pour cause de quota')
        ->assertSee('Inscriptions closes')
        ->assertSee('Fermé pour cause de quota');
});

test('new family cannot register after the modification deadline', function () {
    passModificationDeadline($this->season);

    Livewire::test(GiftRequestForm::class, ['token' => $this->newToken->token])
        ->assertSet('canModify', false)
        ->assertSee('Inscriptions closes');
});

test('family from a previous season without request this season cannot register when blocked', function () {
    blockRegistrations('Fermé');

    $token = EmailToken::createForEmail('former@example.com');
    Family::create([
        'email' => 'former@example.com',
        'first_name' => 'Anne',
        'last_name' => 'Martin',
        'street_name' => 'Rue du Lac',
        'house_no' => '2',
        'postal_code' => '1400',
        'city' => 'Yverdon-les-Bains',
    ]);

    Livewire::test(GiftRequestForm::class, ['token' => $token->token])
        ->assertSet('canModify', false)
        ->assertSet('giftRequest', null)
        ->assertSee('Inscriptions closes');
});

test('registered family can consult its request read only when blocked', function () {
    blockRegistrations('Fermé');

    Livewire::test(GiftRequestForm::class, ['token' => $this->registeredToken->token])
        ->assertSet('canModify', false)
        ->assertSet('isModifying', true)
        ->assertSee('Modification impossible')
        ->assertSee('Fermé')
        ->assertSee('Léa')
        ->assertSee('Poupée');
});

test('registered family can consult its request read only after the modification deadline', function () {
    passModificationDeadline($this->season);

    Livewire::test(GiftRequestForm::class, ['token' => $this->registeredToken->token])
        ->assertSet('canModify', false)
        ->assertSee('Modification impossible')
        ->assertSee('Léa');
});

test('submit is refused when registrations are blocked after the form was opened', function () {
    $component = Livewire::test(GiftRequestForm::class, ['token' => $this->newToken->token])
        ->assertSet('canModify', true);

    blockRegistrations('Fermé pendant la saisie');

    $component->call('submit')
        ->assertSet('canModify', false)
        ->assertSet('registrationClosedMessage', 'Fermé pendant la saisie');

    expect(Family::where('email', 'new@example.com')->exists())->toBeFalse();
});

test('submit does not modify an existing request when registrations are blocked', function () {
    $component = Livewire::test(GiftRequestForm::class, ['token' => $this->registeredToken->token]);

    blockRegistrations('Fermé');

    $component->set('children.0.gift', 'Vélo')
        ->call('submit')
        ->assertSet('submitted', false);

    expect($this->giftRequest->children()->first()->gift)->toBe('Poupée');
});

test('modification access flag cannot be changed from the client', function () {
    blockRegistrations('Fermé');

    Livewire::test(GiftRequestForm::class, ['token' => $this->newToken->token])
        ->set('canModify', true);
})->throws(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);

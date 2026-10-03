<?php

use App\Livewire\Family\GiftRequestForm;
use App\Models\EmailToken;
use App\Models\PickupSlot;
use App\Models\Season;
use App\Models\Setting;
use Livewire\Livewire;

beforeEach(function () {
    Setting::clearCache();

    $this->season = Season::create([
        'name' => 'Test Season',
        'start_date' => now()->subDay(),
        'end_date' => now()->addMonth(),
        'modification_deadline' => now()->addMonth(),
    ]);

    PickupSlot::create([
        'season_id' => $this->season->id,
        'start_datetime' => now()->setDate(2026, 12, 23)->setTime(14, 0),
        'end_datetime' => now()->setDate(2026, 12, 23)->setTime(18, 0),
    ]);

    $this->emailToken = EmailToken::createForEmail('test@example.com');
});

test('gift sizing keyword lists are loaded from settings', function () {
    Setting::setValue(Setting::GIFTS_WITH_SHOE_SIZE, "patin\nroller");
    Setting::setValue(Setting::GIFTS_WITH_SIZE, "veste\npantalon");

    Livewire::test(GiftRequestForm::class, ['token' => $this->emailToken->token])
        ->assertSet('giftsWithShoeSize', ['patin', 'roller'])
        ->assertSet('giftsWithSize', ['veste', 'pantalon']);
});

test('shoe size is required when gift matches configured shoe keywords', function () {
    Setting::setValue(Setting::GIFTS_WITH_SHOE_SIZE, 'patin');

    Livewire::test(GiftRequestForm::class, ['token' => $this->emailToken->token])
        ->set('hasAttemptedSubmit', true)
        ->set('children.0.gift', 'Patin à glace')
        ->set('children.0.shoe_size', '')
        ->call('validateChild', 0)
        ->assertSet('fieldErrors', function (array $errors): bool {
            return ($errors['children.0.shoe_size'][0] ?? null) === 'La pointure est obligatoire pour les chaussures.';
        });
});

test('height is required when gift matches configured size keywords', function () {
    Setting::setValue(Setting::GIFTS_WITH_SIZE, 'veste');

    Livewire::test(GiftRequestForm::class, ['token' => $this->emailToken->token])
        ->set('hasAttemptedSubmit', true)
        ->set('children.0.gift', 'Veste d’hiver')
        ->set('children.0.height', '')
        ->call('validateChild', 0)
        ->assertSet('fieldErrors', function (array $errors): bool {
            return ($errors['children.0.height'][0] ?? null) === 'La taille est obligatoire pour ce cadeau.';
        });
});

test('default shoe keyword fallback still applies when no custom list is set', function () {
    Livewire::test(GiftRequestForm::class, ['token' => $this->emailToken->token])
        ->set('hasAttemptedSubmit', true)
        ->set('children.0.gift', 'Chaussure de sport')
        ->set('children.0.shoe_size', '')
        ->call('validateChild', 0)
        ->assertSet('fieldErrors', function (array $errors): bool {
            return ($errors['children.0.shoe_size'][0] ?? null) === 'La pointure est obligatoire pour les chaussures.';
        });
});

test('shoe size and height fields are shown only when matching gift keywords', function () {
    Setting::setValue(Setting::GIFTS_WITH_SHOE_SIZE, 'patin');
    Setting::setValue(Setting::GIFTS_WITH_SIZE, 'veste');

    $component = Livewire::test(GiftRequestForm::class, ['token' => $this->emailToken->token])
        ->set('step', 2);

    $component
        ->assertDontSee('Pointure (si chaussures)')
        ->assertDontSee('Taille (cm)');

    $component
        ->set('children.0.gift', 'Patin à glace')
        ->assertSee('Pointure (si chaussures)')
        ->assertDontSee('Taille (cm)');

    $component
        ->set('children.0.gift', 'Veste d’hiver')
        ->assertSee('Taille (cm)')
        ->assertDontSee('Pointure (si chaussures)');
});

test('gift keyword matching ignores accents', function () {
    Setting::setValue(Setting::GIFTS_WITH_SHOE_SIZE, 'chaussuré');
    Setting::setValue(Setting::GIFTS_WITH_SIZE, 'vêtement');

    Livewire::test(GiftRequestForm::class, ['token' => $this->emailToken->token])
        ->set('step', 2)
        ->set('children.0.gift', 'Chaussure de sport')
        ->assertSee('Pointure (si chaussures)')
        ->set('children.0.gift', 'Vetement hiver')
        ->assertSee('Taille (cm)');
});

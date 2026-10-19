<?php

use App\Livewire\Family\GiftRequestForm;
use App\Models\EmailToken;
use App\Models\Season;
use App\Models\Setting;
use App\Services\AddressValidationService;
use Livewire\Livewire;

beforeEach(function () {
    Setting::clearCache();

    $this->season = Season::create([
        'name' => 'Test Season',
        'start_date' => now()->subDay(),
        'end_date' => now()->addMonth(),
        'modification_deadline' => now()->addMonth(),
    ]);

    $this->emailToken = EmailToken::createForEmail('wizard@example.com');

    $this->mock(AddressValidationService::class, function ($mock): void {
        $mock->shouldReceive('validate')
            ->andReturn(['Valide' => true, 'Message' => '', 'FormatedAddress' => []]);
    });
});

test('family multi step mode is loaded from settings', function () {
    Setting::setValue(Setting::FAMILY_FORM_MULTI_STEP_ENABLED, '1');

    Livewire::test(GiftRequestForm::class, ['token' => $this->emailToken->token])
        ->assertSet('familyFormMultiStepEnabled', true);
});

test('multi step mode validates each page before continuing', function () {
    Setting::setValue(Setting::FAMILY_FORM_MULTI_STEP_ENABLED, '1');

    $component = Livewire::test(GiftRequestForm::class, ['token' => $this->emailToken->token])
        ->call('acceptConsecutiveYears')
        ->call('acceptPickupCommitment')
        ->call('acceptCity')
        ->assertSet('step', 2)
        ->assertSet('familyFormCurrentStep', 1);

    $component
        ->call('nextFamilyFormStep')
        ->assertSet('familyFormCurrentStep', 1)
        ->assertSet('fieldErrors', function (array $errors): bool {
            return isset($errors['firstName']);
        });

    $component
        ->set('firstName', 'Jean')
        ->set('lastName', 'Dupont')
        ->set('streetName', 'Rue de Test')
        ->set('houseNo', '1')
        ->set('postalCode', '1000')
        ->set('city', 'Lausanne')
        ->set('phone', '+41791234567')
        ->call('nextFamilyFormStep')
        ->assertSet('familyFormCurrentStep', 2);
});

test('multi step mode can reach summary after anonymous and child pages', function () {
    Setting::setValue(Setting::FAMILY_FORM_MULTI_STEP_ENABLED, '1');

    Livewire::test(GiftRequestForm::class, ['token' => $this->emailToken->token])
        ->call('acceptConsecutiveYears')
        ->call('acceptPickupCommitment')
        ->call('acceptCity')
        ->set('firstName', 'Jean')
        ->set('lastName', 'Dupont')
        ->set('streetName', 'Rue de Test')
        ->set('houseNo', '1')
        ->set('postalCode', '1000')
        ->set('city', 'Lausanne')
        ->set('phone', '+41791234567')
        ->call('nextFamilyFormStep')
        ->call('setAnonymousChoice', true)
        ->set('children.0.first_name', 'Lina')
        ->set('children.0.gender', 'girl')
        ->set('children.0.birth_year', '2018')
        ->set('children.0.gift', 'Livre')
        ->call('nextFamilyFormStep')
        ->assertSet('familyFormCurrentStep', 4)
        ->assertSee('Résumé');
});

<?php

use App\Livewire\Family\GiftRequestForm;
use App\Models\EmailToken;
use App\Models\Season;
use App\Models\Setting;
use App\Services\AddressValidationService;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function fakeSwissPostVerification(array $data): void
{
    Http::fake([
        'webservices.post.ch*' => Http::response([
            'QueryBuildingVerification4Result' => [
                'BuildingVerificationData' => array_merge([
                    'HouseNoAddition' => '',
                    'PSTAT' => '1',
                    'StreetName' => 'Rue Edouard Verdan',
                    'TownName' => 'Yverdon-les-Bains',
                    'ZipCode' => '1400',
                ], $data),
            ],
        ]),
    ]);
}

test('formatted house number keeps the house number addition', function () {
    expect(AddressValidationService::formattedHouseNo(['HouseNo' => '24', 'HouseNoAddition' => 'A']))->toBe('24A')
        ->and(AddressValidationService::formattedHouseNo(['HouseNo' => '24', 'HouseNoAddition' => '']))->toBe('24')
        ->and(AddressValidationService::formattedHouseNo(['HouseNo' => '24']))->toBe('24')
        ->and(AddressValidationService::formattedHouseNo(['HouseNo' => '', 'HouseNoAddition' => 'A']))->toBeNull()
        ->and(AddressValidationService::formattedHouseNo([]))->toBeNull();
});

test('address validation keeps the house number addition returned by Swiss Post', function () {
    Setting::clearCache();
    Season::create([
        'name' => 'Test Season',
        'start_date' => now()->subDay(),
        'end_date' => now()->addMonth(),
        'modification_deadline' => now()->addMonth(),
    ]);
    $token = EmailToken::createForEmail('verdan@example.com');

    fakeSwissPostVerification(['HouseNo' => '24', 'HouseNoAddition' => 'A']);

    Livewire::test(GiftRequestForm::class, ['token' => $token->token])
        ->set('streetName', 'rue edouard verdan')
        ->set('houseNo', '24a')
        ->set('postalCode', '1400')
        ->set('city', 'Yverdon-les-Bains')
        ->call('validateAddress')
        ->assertSet('houseNo', '24A')
        ->assertSet('streetName', 'Rue Edouard Verdan')
        ->assertSet('fieldErrors', fn (array $errors): bool => ! isset($errors['address']));
});

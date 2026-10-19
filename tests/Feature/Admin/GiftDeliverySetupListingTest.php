<?php

use App\Livewire\Admin\GiftDelivery;
use App\Models\Child;
use App\Models\Family;
use App\Models\GiftRequest;
use App\Models\Role;
use App\Models\Season;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;

beforeEach(function () {
    Setting::clearCache();

    Role::firstOrCreate(['name' => Role::RECEPTION]);

    $this->receptionUser = User::factory()->create();
    $this->receptionUser->assignRole(Role::RECEPTION);

    $this->season = Season::create([
        'name' => 'Noël 2026',
        'start_date' => now()->subDay(),
        'end_date' => now()->addDays(20),
        'next_family_number' => 1,
    ]);
});

test('delivery page shows setup listing download button', function () {
    $this->actingAs($this->receptionUser)
        ->get(route('admin.delivery'))
        ->assertOk()
        ->assertSee('Télécharger listing mise en place (PDF)');
});

test('can export setup listing pdf from delivery page', function () {
    $family = Family::create([
        'email' => 'famille@example.com',
        'first_name' => 'Anne',
        'last_name' => 'Martin',
        'street_name' => 'Rue de la Gare',
        'house_no' => '7',
        'postal_code' => '1000',
        'city' => 'Lausanne',
        'phone' => '0791234567',
    ]);

    $giftRequest = GiftRequest::create([
        'family_id' => $family->id,
        'season_id' => $this->season->id,
        'family_number' => 7,
        'status' => GiftRequest::STATUS_VALIDATED,
    ]);

    Child::create([
        'gift_request_id' => $giftRequest->id,
        'first_name' => 'Noah',
        'gender' => Child::GENDER_BOY,
        'anonymous' => false,
        'birth_year' => 2017,
        'gift' => 'Vélo bleu',
        'status' => Child::STATUS_RECEIVED,
        'child_number' => 1,
        'code' => 'Y0007/1',
    ]);

    Child::create([
        'gift_request_id' => $giftRequest->id,
        'first_name' => 'Emma',
        'gender' => Child::GENDER_GIRL,
        'anonymous' => false,
        'birth_year' => 2018,
        'gift' => 'Puzzle',
        'status' => Child::STATUS_REJECTED,
        'child_number' => 2,
        'code' => 'Y0007/2',
    ]);

    $this->actingAs($this->receptionUser);

    Livewire::test(GiftDelivery::class)
        ->call('exportSetupListingPdf')
        ->assertFileDownloaded();
});

function createSetupListingRequest(Season $season, int $familyNumber): GiftRequest
{
    $family = Family::create([
        'email' => "famille{$familyNumber}@example.com",
        'first_name' => 'Anne',
        'last_name' => "Famille {$familyNumber}",
        'street_name' => 'Rue de la Gare',
        'house_no' => (string) $familyNumber,
        'postal_code' => '1000',
        'city' => 'Lausanne',
        'phone' => '0791234567',
    ]);

    return GiftRequest::create([
        'family_id' => $family->id,
        'season_id' => $season->id,
        'family_number' => $familyNumber,
        'status' => GiftRequest::STATUS_VALIDATED,
    ]);
}

function createSetupListingChild(GiftRequest $giftRequest, int $childNumber, string $gift, string $status): Child
{
    return Child::create([
        'gift_request_id' => $giftRequest->id,
        'first_name' => "Enfant {$childNumber}",
        'gender' => Child::GENDER_UNSPECIFIED,
        'anonymous' => false,
        'birth_year' => 2018,
        'gift' => $gift,
        'status' => $status,
        'child_number' => $childNumber,
        'code' => sprintf('Y%04d/%d', $giftRequest->family_number, $childNumber),
    ]);
}

/**
 * Export the setup listing and return the pages handed to the PDF view.
 */
function exportSetupListingPages(): \Illuminate\Support\Collection
{
    $pages = null;

    View::composer('pdf.delivery-setup-listing', function ($view) use (&$pages) {
        $pages = $view->getData()['pages'];
    });

    Livewire::test(GiftDelivery::class)
        ->call('exportSetupListingPdf')
        ->assertFileDownloaded();

    return $pages;
}

test('setup listing lists large gifts with full gift name and child code', function () {
    Setting::setValue(Setting::LARGE_GIFTS, "velo\ntrottinette");

    $giftRequest = createSetupListingRequest($this->season, 7);
    createSetupListingChild($giftRequest, 1, 'Vélo bleu', Child::STATUS_RECEIVED);
    createSetupListingChild($giftRequest, 2, 'Puzzle', Child::STATUS_PRINTED);
    createSetupListingChild($giftRequest, 3, 'Grande TROTTINETTE', Child::STATUS_VALIDATED);
    createSetupListingChild($giftRequest, 4, 'Vélo rouge', Child::STATUS_GIVEN);

    $this->actingAs($this->receptionUser);

    $row = exportSetupListingPages()->first()['rows']->first();

    expect($row['family_code'])->toBe(7);
    expect($row['children_count'])->toBe(4);
    expect($row['large_objects']->pluck('display')->all())->toBe([
        'Vélo bleu 7/1',
        'Grande TROTTINETTE 7/3',
        'Vélo rouge 7/4',
    ]);
});

test('setup listing ignores children not eligible for delivery', function () {
    Setting::setValue(Setting::LARGE_GIFTS, 'velo');

    $giftRequest = createSetupListingRequest($this->season, 7);
    createSetupListingChild($giftRequest, 1, 'Vélo bleu', Child::STATUS_RECEIVED);
    createSetupListingChild($giftRequest, 2, 'Vélo vert', Child::STATUS_REJECTED);
    createSetupListingChild($giftRequest, 3, 'Vélo jaune', Child::STATUS_PENDING);

    $this->actingAs($this->receptionUser);

    $row = exportSetupListingPages()->first()['rows']->first();

    expect($row['children_count'])->toBe(1);
    expect($row['large_objects']->pluck('display')->all())->toBe(['Vélo bleu 7/1']);
});

test('setup listing shows no large gifts when the setting is empty', function () {
    $giftRequest = createSetupListingRequest($this->season, 7);
    createSetupListingChild($giftRequest, 1, 'Vélo bleu', Child::STATUS_RECEIVED);

    $this->actingAs($this->receptionUser);

    $row = exportSetupListingPages()->first()['rows']->first();

    expect($row['large_objects'])->toBeEmpty();
});

test('setup listing is limited to the active season and paginated by ten families', function () {
    foreach (range(1, 11) as $familyNumber) {
        createSetupListingChild(
            createSetupListingRequest($this->season, $familyNumber),
            1,
            'Livre',
            Child::STATUS_RECEIVED
        );
    }

    $otherSeason = Season::create([
        'name' => 'Noël 2025',
        'start_date' => now()->subYear(),
        'end_date' => now()->subYear()->addDays(20),
        'next_family_number' => 1,
    ]);
    createSetupListingChild(createSetupListingRequest($otherSeason, 99), 1, 'Livre', Child::STATUS_RECEIVED);

    $this->actingAs($this->receptionUser);

    $pages = exportSetupListingPages();

    expect($pages)->toHaveCount(2);
    expect($pages[0]['first_code'])->toBe(1);
    expect($pages[0]['last_code'])->toBe(10);
    expect($pages[1]['first_code'])->toBe(11);
    expect($pages[1]['last_code'])->toBe(11);
});

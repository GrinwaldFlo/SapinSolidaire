<?php

use App\Livewire\Admin\GiftDelivery;
use App\Models\Child;
use App\Models\Family;
use App\Models\GiftRequest;
use App\Models\Role;
use App\Models\Season;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
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

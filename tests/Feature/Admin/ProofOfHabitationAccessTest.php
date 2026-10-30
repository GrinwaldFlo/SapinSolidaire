<?php

use App\Livewire\Admin\FamilyValidation;
use App\Models\Family;
use App\Models\GiftRequest;
use App\Models\Role;
use App\Models\Season;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');
    Storage::disk('local')->put('proofs/proof.jpg', 'image-content');

    $season = Season::create([
        'name' => 'Noël 2026',
        'start_date' => now()->subDays(10),
        'end_date' => now()->addDays(30),
        'next_family_number' => 1,
    ]);

    $family = Family::create([
        'email' => 'family@example.com',
        'first_name' => 'Jean',
        'last_name' => 'Dupont',
        'street_name' => 'Rue de la Gare',
        'house_no' => '1',
        'postal_code' => '1000',
        'city' => 'Lausanne',
        'phone' => '0791234567',
    ]);

    $this->giftRequest = GiftRequest::create([
        'family_id' => $family->id,
        'season_id' => $season->id,
        'status' => GiftRequest::STATUS_PENDING,
        'proof_of_habitation_path' => 'proofs/proof.jpg',
    ]);
});

function userWithRole(string $role): User
{
    Role::firstOrCreate(['name' => $role]);
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

test('proof of habitation is shown on the family validation page', function () {
    Livewire::actingAs(userWithRole(Role::FAMILY_VALIDATOR))->test(FamilyValidation::class)
        ->assertSee('Justificatif de domicile :')
        ->assertSee(route('admin.proof-of-habitation', $this->giftRequest), false);
});

test('roles validating families can open the proof of habitation', function (string $role) {
    $this->actingAs(userWithRole($role))
        ->get(route('admin.proof-of-habitation', $this->giftRequest))
        ->assertOk();
})->with([Role::FAMILY_VALIDATOR, Role::VALIDATOR, Role::ADMIN]);

test('other roles cannot open the proof of habitation', function (string $role) {
    $this->actingAs(userWithRole($role))
        ->get(route('admin.proof-of-habitation', $this->giftRequest))
        ->assertForbidden();
})->with([Role::ORGANIZER, Role::RECEPTION]);

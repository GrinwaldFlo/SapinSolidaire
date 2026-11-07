<?php

use App\Livewire\Admin\Dashboard;
use App\Models\Child;
use App\Models\Family;
use App\Models\GiftRequest;
use App\Models\Role;
use App\Models\Season;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    Role::firstOrCreate(['name' => Role::ADMIN]);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(Role::ADMIN);

    $this->season = Season::create([
        'name' => 'Noël 2026',
        'start_date' => now()->subDays(10),
        'end_date' => now()->addDays(20),
        'next_family_number' => 1,
    ]);

    $this->previousSeason = Season::create([
        'name' => 'Noël 2025',
        'start_date' => now()->subYear()->subDays(10),
        'end_date' => now()->subYear()->addDays(20),
        'next_family_number' => 1,
    ]);

    $this->createRequest = function (Season $season, array $childStatuses): GiftRequest {
        $family = Family::create([
            'email' => fake()->unique()->safeEmail(),
            'first_name' => 'Paul',
            'last_name' => 'Martin',
            'street_name' => 'Rue Centrale',
            'house_no' => '8',
            'postal_code' => '1000',
            'city' => 'Lausanne',
            'phone' => '0791234567',
        ]);

        $request = GiftRequest::create([
            'family_id' => $family->id,
            'season_id' => $season->id,
            'status' => GiftRequest::STATUS_PENDING,
        ]);

        foreach ($childStatuses as $status) {
            Child::create([
                'gift_request_id' => $request->id,
                'first_name' => 'Alice',
                'gender' => Child::GENDER_GIRL,
                'birth_year' => 2019,
                'gift' => 'Poupée',
                'status' => $status,
            ]);
        }

        return $request;
    };
});

test('dashboard counts rejected children of the active season', function () {
    ($this->createRequest)($this->season, [Child::STATUS_REJECTED, Child::STATUS_REJECTED, Child::STATUS_PENDING]);
    ($this->createRequest)($this->season, [Child::STATUS_REJECTED_FINAL, Child::STATUS_VALIDATED]);
    ($this->createRequest)($this->previousSeason, [Child::STATUS_REJECTED]);

    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)
        ->assertSet('rejectedChildren', 2)
        ->assertSet('pendingChildren', 1)
        ->assertSee('Enfants refusés (cadeau à corriger)');
});

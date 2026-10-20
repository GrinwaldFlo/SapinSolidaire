<?php

use App\Livewire\Admin\DevTools;
use App\Models\Role;
use App\Models\Season;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    Role::firstOrCreate(['name' => Role::ADMIN]);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(Role::ADMIN);
});

test('dev tools page shows season independent tools when no season is active', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.dev-tools'))
        ->assertOk();

    Livewire::actingAs($this->admin)
        ->test(DevTools::class)
        ->assertSee('actuellement active')
        ->assertSee('Récupérer un lien de connexion famille')
        ->assertSee('Supprimer une famille')
        ->assertSee('Zone de danger')
        ->assertDontSee('Générer des familles de test')
        ->assertDontSee('Validation en masse')
        ->assertDontSee('Réception en masse')
        ->assertDontSee('Enfants imprimés');
});

test('dev tools page shows season related tools when a season is active', function () {
    Season::create([
        'name' => 'Noël 2026',
        'start_date' => now()->subDays(10),
        'end_date' => now()->addDays(20),
        'next_family_number' => 1,
    ]);

    Livewire::actingAs($this->admin)
        ->test(DevTools::class)
        ->assertSee('Noël 2026')
        ->assertSee('Générer des familles de test')
        ->assertSee('Validation en masse')
        ->assertSee('Réception en masse')
        ->assertSee('Supprimer une famille')
        ->assertSee('Zone de danger');
});

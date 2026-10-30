<?php

use App\Livewire\Admin\UserManagement;
use App\Models\Role;
use App\Models\User;
use App\Services\RoleAccessMap;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

function rolesForPage(string $label): array
{
    return collect(app(RoleAccessMap::class)->pages())->firstWhere('label', $label)['roles'];
}

test('access map reads the roles from the route middleware', function () {
    expect(rolesForPage('Tableau de bord'))->toEqualCanonicalizing(RoleAccessMap::ROLES)
        ->and(rolesForPage('Validation familles'))->toEqualCanonicalizing([Role::ADMIN, Role::VALIDATOR, Role::FAMILY_VALIDATOR])
        ->and(rolesForPage('Validation cadeaux'))->toEqualCanonicalizing([Role::ADMIN, Role::VALIDATOR])
        ->and(rolesForPage('Cartes'))->toEqualCanonicalizing([Role::ADMIN, Role::ORGANIZER])
        ->and(rolesForPage('Remise'))->toEqualCanonicalizing([Role::ADMIN, Role::RECEPTION])
        ->and(rolesForPage('Utilisateurs'))->toEqual([Role::ADMIN]);
});

test('every admin page of the sidebar is listed in the access map', function () {
    foreach (array_keys(RoleAccessMap::PAGES) as $routeName) {
        expect(Route::has($routeName))->toBeTrue("Missing route {$routeName}");
    }

    expect(app(RoleAccessMap::class)->pages())->toHaveCount(count(RoleAccessMap::PAGES));
});

test('user management page shows the access matrix', function () {
    Role::firstOrCreate(['name' => Role::ADMIN]);
    $admin = User::factory()->create();
    $admin->assignRole(Role::ADMIN);

    Livewire::actingAs($admin)->test(UserManagement::class)
        ->assertSee('Accès aux pages par rôle')
        ->assertSee('Validation familles')
        ->assertSee(ucfirst(Role::FAMILY_VALIDATOR));
});

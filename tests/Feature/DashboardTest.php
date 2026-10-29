<?php

use App\Models\Setting;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});
test('dashboard and sidebar show the configured site name', function () {
    Setting::setValue(Setting::SITE_NAME, 'Noël Partagé');
    $this->actingAs(User::factory()->create());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Bienvenue sur Noël Partagé')
        ->assertDontSee('Sapin Solidaire');
});

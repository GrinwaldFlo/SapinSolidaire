<?php

use App\Models\User;

test('pages have the pink test background when APP_ENV is test', function () {
    app()->detectEnvironment(fn () => 'test');

    $this->get(route('home'))->assertSee('env-test');
    $this->get(route('login'))->assertSee('env-test');

    $this->actingAs(User::factory()->create());
    $this->get(route('dashboard'))->assertSee('env-test');
});

test('pages do not have the pink test background outside the test environment', function () {
    $this->get(route('home'))->assertDontSee('env-test');
    $this->get(route('login'))->assertDontSee('env-test');

    $this->actingAs(User::factory()->create());
    $this->get(route('dashboard'))->assertDontSee('env-test');
});

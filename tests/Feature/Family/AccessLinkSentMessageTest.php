<?php

use App\Livewire\Family\Home;
use App\Models\Season;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();
});

function createActiveSeason(array $attributes = []): Season
{
    return Season::create(array_merge([
        'name' => 'Noël 2026',
        'start_date' => now()->subDay(),
        'end_date' => now()->addMonth(),
    ], $attributes));
}

test('the sent link screen asks to check spam and mailbox space', function () {
    createActiveSeason();

    Livewire::test(Home::class)
        ->set('email', 'family@example.com')
        ->call('sendLink')
        ->assertSet('emailSent', true)
        ->assertSee('spam')
        ->assertSee('assez d\'espace', false)
        ->assertSee('family@example.com');
});

test('the sent link screen shows the season contact email', function () {
    createActiveSeason(['responsible_email' => 'contact@sapin.ch']);

    Livewire::test(Home::class)
        ->set('email', 'family@example.com')
        ->call('sendLink')
        ->assertSee('En cas de problème')
        ->assertSeeHtml('href="mailto:contact@sapin.ch"');
});

test('the sent link screen hides the contact line without season contact email', function () {
    createActiveSeason();

    Livewire::test(Home::class)
        ->set('email', 'family@example.com')
        ->call('sendLink')
        ->assertDontSee('En cas de problème');
});

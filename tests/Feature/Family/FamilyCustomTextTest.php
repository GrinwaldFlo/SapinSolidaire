<?php

use App\Livewire\Family\Home;
use App\Models\Season;
use App\Models\Setting;
use App\Services\CustomText;
use Livewire\Livewire;

beforeEach(function () {
    Setting::clearCache();
});

test('home page shows the custom introduction text', function () {
    Season::create([
        'name' => 'Active',
        'start_date' => now()->subDay(),
        'end_date' => now()->addMonth(),
    ]);
    Setting::setValue(CustomText::HOME_INTRODUCTION, 'Bienvenue chez {site}');

    Livewire::test(Home::class)->assertSee('Bienvenue chez Sapin Solidaire');
});

test('urls in custom texts are shown as links on family pages', function () {
    Season::create([
        'name' => 'Active',
        'start_date' => now()->subDay(),
        'end_date' => now()->addMonth(),
    ]);
    Setting::setValue(CustomText::HOME_INTRODUCTION, 'Infos sur www.e-f-y.ch');

    Livewire::test(Home::class)
        ->assertSeeHtml('<a href="https://www.e-f-y.ch" class="link"');
});

test('home page shows the custom pickup slots explanation', function () {
    $season = Season::create([
        'name' => 'Active',
        'start_date' => now()->subDay(),
        'end_date' => now()->addMonth(),
    ]);
    $season->pickupSlots()->create([
        'start_datetime' => now()->addDays(5)->setTime(9, 0),
        'end_datetime' => now()->addDays(5)->setTime(12, 0),
    ]);
    Setting::setValue(CustomText::HOME_PICKUP_SLOTS_INFO, 'Venez à votre créneau.');

    Livewire::test(Home::class)
        ->assertSee('Venez à votre créneau.')
        ->assertDontSee(CustomText::default(CustomText::HOME_PICKUP_SLOTS_INFO));
});

test('home page shows the custom future season message with its opening date', function () {
    $season = Season::create([
        'name' => 'Future',
        'start_date' => now()->addWeek(),
        'end_date' => now()->addMonth(),
    ]);
    Setting::setValue(CustomText::SEASON_FUTURE, 'Ouverture le {date_ouverture} !');

    Livewire::test(Home::class)
        ->assertSee('Ouverture le '.$season->start_date->format('d/m/Y').' !');
});

test('home page shows the default message when no season is planned', function () {
    Livewire::test(Home::class)
        ->assertSee(CustomText::default(CustomText::SEASON_NONE));
});

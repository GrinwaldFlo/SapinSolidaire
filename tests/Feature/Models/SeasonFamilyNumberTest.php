<?php

use App\Models\Family;
use App\Models\GiftRequest;
use App\Models\Season;

beforeEach(function () {
    $this->season = Season::create([
        'name' => 'Noël 2025',
        'start_date' => now()->subDays(10),
        'end_date' => now()->addDays(30),
        'next_family_number' => 1,
    ]);
});

test('assignNextFamilyNumber returns 1 for a new season', function () {
    $number = $this->season->assignNextFamilyNumber();

    expect($number)->toBe(1);
});

test('assignNextFamilyNumber increments the counter after each call', function () {
    $first = $this->season->assignNextFamilyNumber();
    $second = $this->season->assignNextFamilyNumber();
    $third = $this->season->assignNextFamilyNumber();

    expect($first)->toBe(1);
    expect($second)->toBe(2);
    expect($third)->toBe(3);
});

test('assignNextFamilyNumber produces no gaps in sequential calls', function () {
    $numbers = [];
    for ($i = 0; $i < 10; $i++) {
        $numbers[] = $this->season->assignNextFamilyNumber();
    }

    expect($numbers)->toBe([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);
});

test('assignNextFamilyNumber updates next_family_number in database', function () {
    $this->season->assignNextFamilyNumber();
    $this->season->assignNextFamilyNumber();
    $this->season->assignNextFamilyNumber();

    $season = Season::find($this->season->id);
    expect($season->next_family_number)->toBe(4);
});

test('each season has independent family number counters', function () {
    $season2 = Season::create([
        'name' => 'Noël 2026',
        'start_date' => now()->addYear()->subDays(10),
        'end_date' => now()->addYear()->addDays(30),
        'next_family_number' => 1,
    ]);

    $num1 = $this->season->assignNextFamilyNumber();
    $num2 = $this->season->assignNextFamilyNumber();
    $num3 = $season2->assignNextFamilyNumber();

    expect($num1)->toBe(1);
    expect($num2)->toBe(2);
    expect($num3)->toBe(1); // independent counter
});

test('season next_family_number defaults to 1', function () {
    $season = Season::create([
        'name' => 'Noël 2027',
        'start_date' => now()->addYears(2)->subDays(10),
        'end_date' => now()->addYears(2)->addDays(30),
    ]);

    // Default from migration is 1
    expect($season->fresh()->next_family_number)->toBe(1);
});

<?php

use App\Models\Child;
use App\Models\Family;
use App\Models\GiftRequest;
use App\Models\Season;
use App\Models\Setting;
use App\Services\NumberSequence;

beforeEach(function () {
    Setting::clearCache();
    Setting::setValue(Setting::CODE_PREFIX, 'Y');

    $this->season = Season::create([
        'name' => 'Noël 2025',
        'start_date' => now()->subDays(10),
        'end_date' => now()->addDays(30),
    ]);

    $this->makeRequest = function (Season $season, ?int $familyNumber = null): GiftRequest {
        $family = Family::create([
            'email' => uniqid('famille').'@example.com',
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'street_name' => 'Rue de la Gare',
            'house_no' => '1',
            'postal_code' => '1000',
            'city' => 'Lausanne',
            'phone' => '0791234567',
        ]);

        return GiftRequest::create([
            'family_id' => $family->id,
            'season_id' => $season->id,
            'family_number' => $familyNumber,
            'status' => GiftRequest::STATUS_VALIDATED,
        ]);
    };

    $this->makeChild = fn (GiftRequest $request, ?int $childNumber = null) => Child::create([
        'gift_request_id' => $request->id,
        'first_name' => 'Enfant',
        'gender' => Child::GENDER_GIRL,
        'birth_year' => 2018,
        'gift' => 'Livre',
        'status' => Child::STATUS_VALIDATED,
        'child_number' => $childNumber,
    ]);
});

test('the first free number fills gaps', function () {
    expect(NumberSequence::firstFree([]))->toBe(1);
    expect(NumberSequence::firstFree([1, 2, 3]))->toBe(4);
    expect(NumberSequence::firstFree([1, 3, 4]))->toBe(2);
    expect(NumberSequence::firstFree([2, 3]))->toBe(1);
    expect(NumberSequence::firstFree(['1', null, '2']))->toBe(3);
});

test('family numbers follow each other in a season', function () {
    $numbers = collect(range(1, 3))
        ->map(fn () => ($this->makeRequest)($this->season)->assignFamilyNumber())
        ->all();

    expect($numbers)->toBe([1, 2, 3]);
});

test('a family number fills the gap left in the season', function () {
    ($this->makeRequest)($this->season, 1);
    ($this->makeRequest)($this->season, 3);

    expect(($this->makeRequest)($this->season)->assignFamilyNumber())->toBe(2);
    expect(($this->makeRequest)($this->season)->assignFamilyNumber())->toBe(4);
});

test('a family number is stored and never changes', function () {
    $request = ($this->makeRequest)($this->season);

    expect($request->assignFamilyNumber())->toBe(1);
    expect($request->fresh()->family_number)->toBe(1);

    expect($request->fresh()->assignFamilyNumber())->toBe(1);

    $request->update(['family_number' => 5]);
    expect($request->fresh()->assignFamilyNumber())->toBe(5);
});

test('each season has its own family numbers', function () {
    $season2 = Season::create([
        'name' => 'Noël 2026',
        'start_date' => now()->addYear()->subDays(10),
        'end_date' => now()->addYear()->addDays(30),
    ]);
    ($this->makeRequest)($this->season, 1);

    expect(($this->makeRequest)($season2)->assignFamilyNumber())->toBe(1);
});

test('a child number fills the gap left in its family', function () {
    $request = ($this->makeRequest)($this->season, 4);
    ($this->makeChild)($request, 1);
    ($this->makeChild)($request, 3);

    $child = ($this->makeChild)($request);
    $child->assignChildNumberAndCode();

    expect($child->child_number)->toBe(2);
    expect($child->code)->toBe('Y0004/2');
});

test('assigning a child code also gives the family its number', function () {
    ($this->makeRequest)($this->season, 1);
    $request = ($this->makeRequest)($this->season);

    $child = ($this->makeChild)($request);
    $child->assignChildNumberAndCode();

    expect($request->fresh()->family_number)->toBe(2);
    expect($child->code)->toBe('Y0002/1');
});

test('a child code never changes once assigned', function () {
    $request = ($this->makeRequest)($this->season, 4);
    $child = ($this->makeChild)($request);
    $child->update(['child_number' => 3, 'code' => 'Y0004/3']);

    $child->fresh()->assignChildNumberAndCode();

    expect($child->fresh()->child_number)->toBe(3);
    expect($child->fresh()->code)->toBe('Y0004/3');
});

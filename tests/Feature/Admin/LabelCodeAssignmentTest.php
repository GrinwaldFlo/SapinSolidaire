<?php

use App\Livewire\Admin\LabelGeneration;
use App\Models\Child;
use App\Models\Family;
use App\Models\GiftRequest;
use App\Models\Role;
use App\Models\Season;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * Family numbers and child codes are given when the gift cards are printed, never before.
 */
beforeEach(function () {
    Setting::clearCache();
    Setting::setValue(Setting::CODE_PREFIX, 'Y');
    Storage::fake('local');
    Mail::fake();

    Role::firstOrCreate(['name' => Role::ADMIN]);
    $this->admin = User::factory()->create();
    $this->admin->assignRole(Role::ADMIN);

    $this->season = Season::create([
        'name' => 'Noël 2026',
        'start_date' => now()->subDays(10),
        'end_date' => now()->addDays(30),
    ]);

    $this->makeRequest = function (string $email, ?int $familyNumber = null): GiftRequest {
        $family = Family::create([
            'email' => $email,
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
            'season_id' => $this->season->id,
            'family_number' => $familyNumber,
            'status' => GiftRequest::STATUS_VALIDATED,
        ]);
    };

    $this->makeChild = fn (GiftRequest $request, string $status = Child::STATUS_VALIDATED, ?int $validatedMinutesAgo = 10) => Child::create([
        'gift_request_id' => $request->id,
        'first_name' => 'Enfant',
        'gender' => Child::GENDER_GIRL,
        'birth_year' => 2018,
        'gift' => 'Livre',
        'status' => $status,
        'validated_at' => $status === Child::STATUS_VALIDATED ? now()->subMinutes($validatedMinutesAgo) : null,
    ]);

    $this->print = function (): void {
        $this->actingAs($this->admin);
        Livewire::test(LabelGeneration::class)->call('generatePdf');
    };
});

test('printing gives family numbers in validation order and child codes', function () {
    $later = ($this->makeRequest)('later@example.com');
    $earlier = ($this->makeRequest)('earlier@example.com');
    $laterChild = ($this->makeChild)($later, Child::STATUS_VALIDATED, 5);
    $earlierFirst = ($this->makeChild)($earlier, Child::STATUS_VALIDATED, 30);
    $earlierSecond = ($this->makeChild)($earlier, Child::STATUS_VALIDATED, 20);

    ($this->print)();

    expect($earlier->fresh()->family_number)->toBe(1);
    expect($later->fresh()->family_number)->toBe(2);
    expect($earlierFirst->fresh()->code)->toBe('Y0001/1');
    expect($earlierSecond->fresh()->code)->toBe('Y0001/2');
    expect($laterChild->fresh()->code)->toBe('Y0002/1');
    expect($laterChild->fresh()->status)->toBe(Child::STATUS_PRINTED);
});

test('children not printed get no code and their family no number', function () {
    $request = ($this->makeRequest)('pending@example.com');
    $child = ($this->makeChild)($request, Child::STATUS_PENDING);

    ($this->print)();

    expect($request->fresh()->family_number)->toBeNull();
    expect($child->fresh()->code)->toBeNull();
    expect($child->fresh()->child_number)->toBeNull();
});

test('a family number left free in the season is reused', function () {
    ($this->makeRequest)('first@example.com', 1);
    ($this->makeRequest)('third@example.com', 3);
    $request = ($this->makeRequest)('new@example.com');
    $child = ($this->makeChild)($request);

    ($this->print)();

    expect($request->fresh()->family_number)->toBe(2);
    expect($child->fresh()->code)->toBe('Y0002/1');
});

test('a child printed later continues the numbering of its family', function () {
    $request = ($this->makeRequest)('family@example.com');
    $first = ($this->makeChild)($request);
    $second = ($this->makeChild)($request, Child::STATUS_PENDING);

    ($this->print)();

    $second->setStatus(Child::STATUS_VALIDATED);
    ($this->print)();

    expect($first->fresh()->code)->toBe('Y0001/1');
    expect($second->fresh()->code)->toBe('Y0001/2');
    expect($request->fresh()->family_number)->toBe(1);
});

test('reprinting after a label reset keeps the codes', function () {
    ($this->makeRequest)('other@example.com');
    $request = ($this->makeRequest)('family@example.com');
    $child = ($this->makeChild)($request);

    ($this->print)();
    expect($child->fresh()->code)->toBe('Y0001/1');

    Livewire::test(LabelGeneration::class)->call('resetPrintedLabels');
    expect($child->fresh()->status)->toBe(Child::STATUS_VALIDATED);

    ($this->print)();

    expect($child->fresh()->status)->toBe(Child::STATUS_PRINTED);
    expect($child->fresh()->code)->toBe('Y0001/1');
    expect($request->fresh()->family_number)->toBe(1);
});

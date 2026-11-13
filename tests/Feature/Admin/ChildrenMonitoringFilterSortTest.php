<?php

use App\Livewire\Admin\ChildrenMonitoring;
use App\Models\Child;
use App\Models\Family;
use App\Models\GiftRequest;
use App\Models\Role;
use App\Models\Season;
use App\Models\User;
use Livewire\Livewire;

function createSortableMonitoringChild(Season $season, string $lastName, string $firstName, string $status): Child
{
    $family = Family::create([
        'email' => strtolower($lastName).'@example.com',
        'first_name' => 'Parent',
        'last_name' => $lastName,
        'street_name' => 'Rue Centrale',
        'house_no' => '10',
        'postal_code' => '1000',
        'city' => 'Lausanne',
        'phone' => '0791112233',
    ]);

    $giftRequest = GiftRequest::create([
        'family_id' => $family->id,
        'season_id' => $season->id,
        'status' => GiftRequest::STATUS_VALIDATED,
    ]);

    return Child::create([
        'gift_request_id' => $giftRequest->id,
        'first_name' => $firstName,
        'gender' => Child::GENDER_GIRL,
        'birth_year' => now()->year - 6,
        'gift' => 'Peluche',
        'status' => $status,
    ]);
}

beforeEach(function () {
    Role::firstOrCreate(['name' => Role::ORGANIZER]);

    $organizer = User::factory()->create();
    $organizer->assignRole(Role::ORGANIZER);
    $this->actingAs($organizer);

    $season = Season::create([
        'name' => 'Noël 2026',
        'start_date' => now()->subDays(10),
        'end_date' => now()->addDays(10),
    ]);

    createSortableMonitoringChild($season, 'Zurbriggen', 'Anna', Child::STATUS_PENDING);
    createSortableMonitoringChild($season, 'Aebischer', 'Zoe', Child::STATUS_PENDING);
    createSortableMonitoringChild($season, 'Muller', 'Leo', Child::STATUS_VALIDATED);
});

test('sorting by family name works together with the status filter and search', function () {
    Livewire::test(ChildrenMonitoring::class)
        ->set('statusFilter', Child::STATUS_PENDING)
        ->call('sort', 'family_name')
        ->assertOk()
        ->assertSeeInOrder(['Zoe', 'Anna'])
        ->assertDontSee('Leo')
        ->set('search', 'Zurbriggen')
        ->assertOk()
        ->assertSee('Anna')
        ->assertDontSee('Zoe');
});

test('sorting by status works together with the family name join', function () {
    Livewire::test(ChildrenMonitoring::class)
        ->call('sort', 'family_name')
        ->call('sort', 'status')
        ->assertOk()
        ->assertSee('Leo');
});

test('an unknown sort column set by the browser falls back to the first name', function () {
    Livewire::test(ChildrenMonitoring::class)
        ->set('sortBy', 'id; drop table children')
        ->assertOk()
        ->assertSeeInOrder(['Anna', 'Leo', 'Zoe']);
});

test('pdf export works with the family name sort and status filter', function () {
    Livewire::test(ChildrenMonitoring::class)
        ->set('statusFilter', Child::STATUS_PENDING)
        ->call('sort', 'family_name')
        ->call('exportPdf')
        ->assertOk();
});

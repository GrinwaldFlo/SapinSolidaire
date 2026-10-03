<?php

use App\Livewire\Admin\FamilyManagement;
use App\Models\Child;
use App\Models\Family;
use App\Models\GiftRequest;
use App\Models\Role;
use App\Models\Season;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    Role::firstOrCreate(['name' => Role::ADMIN]);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(Role::ADMIN);

    $this->season = Season::create([
        'name' => 'Noël 2026',
        'start_date' => now()->subDays(10),
        'end_date' => now()->addDays(20),
        'next_family_number' => 1,
    ]);

    $this->family = Family::create([
        'email' => 'family-management@example.com',
        'first_name' => 'Paul',
        'last_name' => 'Martin',
        'street_name' => 'Rue Centrale',
        'house_no' => '8',
        'postal_code' => '1000',
        'city' => 'Lausanne',
        'phone' => '0791234567',
    ]);

    $this->giftRequest = GiftRequest::create([
        'family_id' => $this->family->id,
        'season_id' => $this->season->id,
        'status' => GiftRequest::STATUS_REJECTED_FINAL,
        'rejection_comment' => 'Refus final',
    ]);
});

test('row action can reset request and children to pending', function () {
    $child = Child::create([
        'gift_request_id' => $this->giftRequest->id,
        'first_name' => 'Alice',
        'gender' => Child::GENDER_GIRL,
        'birth_year' => 2019,
        'gift' => 'Poupée',
        'status' => Child::STATUS_REJECTED_FINAL,
        'rejection_comment' => 'Refus final enfant',
    ]);

    $this->actingAs($this->admin);

    Livewire::test(FamilyManagement::class)
        ->call('applyAction', $this->family->id, $this->giftRequest->id, 'reset_pending');

    $this->giftRequest->refresh();
    $child->refresh();

    expect($this->giftRequest->status)->toBe(GiftRequest::STATUS_PENDING);
    expect($this->giftRequest->rejection_comment)->toBeNull();
    expect($child->status)->toBe(Child::STATUS_PENDING);
    expect($child->rejection_comment)->toBeNull();
});

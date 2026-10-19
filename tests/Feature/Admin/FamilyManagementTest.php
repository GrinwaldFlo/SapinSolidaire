<?php

use App\Livewire\Admin\FamilyManagement;
use App\Models\AdminActionLog;
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
        ->call('applyAction', $this->family->id, 'reset_pending');

    $this->giftRequest->refresh();
    $child->refresh();

    expect($this->giftRequest->status)->toBe(GiftRequest::STATUS_PENDING);
    expect($this->giftRequest->rejection_comment)->toBeNull();
    expect($child->status)->toBe(Child::STATUS_PENDING);
    expect($child->rejection_comment)->toBeNull();
});

test('row action can exclude a family and logs it', function () {
    $this->giftRequest->setStatus(GiftRequest::STATUS_VALIDATED);

    $child = Child::create([
        'gift_request_id' => $this->giftRequest->id,
        'first_name' => 'Alice',
        'gender' => Child::GENDER_GIRL,
        'birth_year' => 2019,
        'gift' => 'Poupée',
        'status' => Child::STATUS_VALIDATED,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(FamilyManagement::class)
        ->call('applyAction', $this->family->id, 'exclude', 'Hors zone');

    expect($this->giftRequest->refresh()->status)->toBe(GiftRequest::STATUS_REJECTED_FINAL);
    expect($this->giftRequest->rejection_comment)->toBe('Hors zone');
    expect($child->refresh()->status)->toBe(Child::STATUS_REJECTED_FINAL);

    $log = AdminActionLog::where('action_type', AdminActionLog::ACTION_FAMILY_EXCLUDED)->first();
    expect($log)->not->toBeNull();
    expect($log->family_id)->toBe($this->family->id);
    expect($log->description)->toContain('Hors zone');
});

test('excluding a family requires a reason', function () {
    $this->giftRequest->setStatus(GiftRequest::STATUS_VALIDATED);

    $this->actingAs($this->admin);

    Livewire::test(FamilyManagement::class)
        ->call('applyAction', $this->family->id, 'exclude', '  ');

    expect($this->giftRequest->refresh()->status)->toBe(GiftRequest::STATUS_VALIDATED);
    expect(AdminActionLog::where('action_type', AdminActionLog::ACTION_FAMILY_EXCLUDED)->exists())->toBeFalse();
});

test('excluding a family marks the family as excluded', function () {
    $this->giftRequest->setStatus(GiftRequest::STATUS_VALIDATED);
    $this->family->clearExclusion();

    $this->actingAs($this->admin);

    Livewire::test(FamilyManagement::class)
        ->call('applyAction', $this->family->id, 'exclude', 'Fraude');

    $this->family->refresh();
    expect($this->family->isExcluded())->toBeTrue();
    expect($this->family->exclusion_reason)->toBe('Fraude');
});

test('a family without request in the active season can be excluded', function () {
    $this->giftRequest->delete();

    $this->actingAs($this->admin);

    Livewire::test(FamilyManagement::class)
        ->call('applyAction', $this->family->id, 'exclude', 'Comportement inacceptable');

    $this->family->refresh();
    expect($this->family->isExcluded())->toBeTrue();
    expect($this->family->exclusion_reason)->toBe('Comportement inacceptable');
    expect(GiftRequest::where('family_id', $this->family->id)->exists())->toBeFalse();

    $log = AdminActionLog::where('action_type', AdminActionLog::ACTION_FAMILY_EXCLUDED)->first();
    expect($log->family_id)->toBe($this->family->id);
    expect($log->gift_request_id)->toBeNull();
});

test('excluding a family does not modify requests from past seasons', function () {
    $this->giftRequest->delete();

    $pastSeason = Season::create([
        'name' => 'Noël 2025',
        'start_date' => now()->subYear()->subDays(10),
        'end_date' => now()->subYear()->addDays(20),
        'next_family_number' => 1,
    ]);
    $pastRequest = GiftRequest::create([
        'family_id' => $this->family->id,
        'season_id' => $pastSeason->id,
        'status' => GiftRequest::STATUS_VALIDATED,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(FamilyManagement::class)
        ->call('applyAction', $this->family->id, 'exclude', 'Motif');

    expect($pastRequest->refresh()->status)->toBe(GiftRequest::STATUS_VALIDATED);
    expect($this->family->refresh()->isExcluded())->toBeTrue();
});

test('exclusion can be lifted for a family without request in the active season', function () {
    $this->giftRequest->delete();
    $this->family->exclude('Motif');

    $this->actingAs($this->admin);

    Livewire::test(FamilyManagement::class)
        ->call('applyAction', $this->family->id, 'reset_pending');

    $this->family->refresh();
    expect($this->family->isExcluded())->toBeFalse();
    expect($this->family->exclusion_reason)->toBeNull();
});

test('resetting the active request lifts the family exclusion', function () {
    $this->family->exclude('Refus final');

    $this->actingAs($this->admin);

    Livewire::test(FamilyManagement::class)
        ->call('applyAction', $this->family->id, 'reset_pending');

    expect($this->family->refresh()->isExcluded())->toBeFalse();
});

test('action column shows status badges', function () {
    $this->actingAs($this->admin);

    Livewire::test(FamilyManagement::class)
        ->assertSee('Refusé définitivement');

    $this->giftRequest->delete();
    $this->family->exclude('Motif');

    Livewire::test(FamilyManagement::class)
        ->assertSee('Refusé définitivement')
        ->assertDontSee('Aucune demande cette saison')
        ->assertDontSee('Exclue')
        ->assertSee("Lever l'exclusion", false);
});

test('rejected final filter includes families excluded without request', function () {
    $this->giftRequest->delete();
    $this->family->exclude('Motif');

    $this->actingAs($this->admin);

    Livewire::test(FamilyManagement::class)
        ->set('statusFilter', GiftRequest::STATUS_REJECTED_FINAL)
        ->assertSee('family-management@example.com');
});

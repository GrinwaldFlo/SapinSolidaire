<?php

use App\Livewire\Admin\FamilyValidation;
use App\Models\Family;
use App\Models\GiftRequest;
use App\Models\Role;
use App\Models\Season;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();

    Role::firstOrCreate(['name' => Role::FAMILY_VALIDATOR]);
    $this->validator = User::factory()->create();
    $this->validator->assignRole(Role::FAMILY_VALIDATOR);

    $this->season = Season::create([
        'name' => 'Noël 2026',
        'start_date' => now()->subDays(10),
        'end_date' => now()->addDays(30),
        'next_family_number' => 1,
    ]);

    $this->family = Family::create([
        'email' => 'family@example.com',
        'first_name' => 'Jean',
        'last_name' => 'Dupont',
        'street_name' => 'Rue de la Gare',
        'house_no' => '1',
        'postal_code' => '1000',
        'city' => 'Lausanne',
        'phone' => '0791234567',
    ]);

    $this->pendingRequest = GiftRequest::create([
        'family_id' => $this->family->id,
        'season_id' => $this->season->id,
        'status' => GiftRequest::STATUS_PENDING,
    ]);

    $oldSeason = Season::create([
        'name' => 'Noël 2020',
        'start_date' => now()->subYears(6),
        'end_date' => now()->subYears(6)->addMonth(),
        'next_family_number' => 1,
    ]);

    $this->oldRequest = GiftRequest::create([
        'family_id' => $this->family->id,
        'season_id' => $oldSeason->id,
        'status' => GiftRequest::STATUS_VALIDATED,
    ]);
});

test('rejection applies to the request assigned to the validator', function () {
    Livewire::actingAs($this->validator)->test(FamilyValidation::class)
        ->call('openRejectionModal', true)
        ->set('rejectionComment', 'Dossier incomplet, refus définitif.')
        ->call('confirmRejection');

    expect($this->pendingRequest->fresh()->status)->toBe(GiftRequest::STATUS_REJECTED_FINAL);
    expect($this->oldRequest->fresh()->status)->toBe(GiftRequest::STATUS_VALIDATED);
});

test('rejection target cannot be chosen from the browser', function () {
    Livewire::actingAs($this->validator)->test(FamilyValidation::class)
        ->set('rejectionTargetId', $this->oldRequest->id);
})->throws(\Livewire\Exceptions\PublicPropertyNotFoundException::class);

test('a request no longer pending is not rejected', function () {
    $component = Livewire::actingAs($this->validator)->test(FamilyValidation::class);

    $this->pendingRequest->update(['status' => GiftRequest::STATUS_VALIDATED]);

    $component->call('openRejectionModal', true)
        ->set('rejectionComment', 'Dossier incomplet, refus définitif.')
        ->call('confirmRejection');

    expect($this->pendingRequest->fresh()->status)->toBe(GiftRequest::STATUS_VALIDATED);
    Mail::assertNothingQueued();
});

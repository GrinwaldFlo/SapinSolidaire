<?php

use App\Livewire\Admin\FamilyManagement;
use App\Livewire\Admin\Validation;
use App\Models\AdminActionLog;
use App\Models\Child;
use App\Models\Family;
use App\Models\GiftRequest;
use App\Models\Role;
use App\Models\Season;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    Role::firstOrCreate(['name' => Role::ADMIN]);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(Role::ADMIN);

    $this->season = Season::create([
        'name' => 'Noël 2026',
        'start_date' => now()->subDays(10),
        'end_date' => now()->addDays(10),
        'next_family_number' => 1,
    ]);

    $this->family = Family::create([
        'email' => 'audit-admin@example.com',
        'first_name' => 'Nina',
        'last_name' => 'Rossi',
        'street_name' => 'Rue Centrale',
        'house_no' => '10',
        'postal_code' => '1000',
        'city' => 'Lausanne',
        'phone' => '0791112233',
    ]);
});

test('reset from family management writes an admin action log', function () {
    $giftRequest = GiftRequest::create([
        'family_id' => $this->family->id,
        'season_id' => $this->season->id,
        'status' => GiftRequest::STATUS_REJECTED_FINAL,
    ]);

    Child::create([
        'gift_request_id' => $giftRequest->id,
        'first_name' => 'Lia',
        'gender' => Child::GENDER_GIRL,
        'birth_year' => 2018,
        'gift' => 'Poupée',
        'status' => Child::STATUS_REJECTED_FINAL,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(FamilyManagement::class)
        ->call('applyAction', $this->family->id, $giftRequest->id, 'reset_pending');

    $this->assertDatabaseHas('admin_action_logs', [
        'action_type' => AdminActionLog::ACTION_FAMILY_STATUS_RESET,
        'user_id' => $this->admin->id,
        'gift_request_id' => $giftRequest->id,
    ]);
});

test('combined validation writes logs for validated family and child', function () {
    $giftRequest = GiftRequest::create([
        'family_id' => $this->family->id,
        'season_id' => $this->season->id,
        'status' => GiftRequest::STATUS_PENDING,
    ]);

    $child = Child::create([
        'gift_request_id' => $giftRequest->id,
        'first_name' => 'Noah',
        'gender' => Child::GENDER_BOY,
        'birth_year' => 2017,
        'gift' => 'Lego',
        'status' => Child::STATUS_PENDING,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(Validation::class)
        ->set('familyDecision', 'validated')
        ->set("childDecisions.{$child->id}", 'validated')
        ->call('submitValidation');

    $this->assertDatabaseHas('admin_action_logs', [
        'action_type' => AdminActionLog::ACTION_FAMILY_VALIDATED,
        'user_id' => $this->admin->id,
        'gift_request_id' => $giftRequest->id,
    ]);

    $this->assertDatabaseHas('admin_action_logs', [
        'action_type' => AdminActionLog::ACTION_CHILD_VALIDATED,
        'user_id' => $this->admin->id,
        'child_id' => $child->id,
        'description' => 'Validation enfant: Noah (famille: Nina Rossi)',
    ]);
});

test('validation correction email is also logged in admin actions', function () {
    Mail::fake();

    $giftRequest = GiftRequest::create([
        'family_id' => $this->family->id,
        'season_id' => $this->season->id,
        'status' => GiftRequest::STATUS_PENDING,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(Validation::class)
        ->set('familyDecision', 'correction')
        ->set('familyComment', 'Merci de corriger la demande.')
        ->call('submitValidation');

    $this->assertDatabaseHas('admin_action_logs', [
        'action_type' => AdminActionLog::ACTION_EMAIL_SENT,
        'user_id' => $this->admin->id,
        'gift_request_id' => $giftRequest->id,
    ]);
});

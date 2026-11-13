<?php

use App\Livewire\Admin\ChildrenMonitoring;
use App\Models\AdminActionLog;
use App\Models\Child;
use App\Models\Family;
use App\Models\GiftRequest;
use App\Models\Role;
use App\Models\Season;
use App\Models\User;
use Livewire\Livewire;

function createMonitoringChild(Season $season, string $status, string $email): Child
{
    $family = Family::create([
        'email' => $email,
        'first_name' => 'Nina',
        'last_name' => 'Rossi',
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
        'first_name' => 'Lia',
        'gender' => Child::GENDER_GIRL,
        'birth_year' => now()->year - 6,
        'gift' => 'Peluche',
        'status' => $status,
        'rejection_comment' => $status === Child::STATUS_REJECTED ? 'Cadeau trop cher' : null,
    ]);
}

beforeEach(function () {
    Role::firstOrCreate(['name' => Role::ADMIN]);
    Role::firstOrCreate(['name' => Role::ORGANIZER]);

    $this->admin = User::factory()->create(['name' => 'Alice Admin']);
    $this->admin->assignRole(Role::ADMIN);

    $this->organizer = User::factory()->create();
    $this->organizer->assignRole(Role::ORGANIZER);

    $this->season = Season::create([
        'name' => 'Noël 2026',
        'start_date' => now()->subDays(10),
        'end_date' => now()->addDays(10),
    ]);

    $this->pastSeason = Season::create([
        'name' => 'Noël 2025',
        'start_date' => now()->subYear()->subDays(10),
        'end_date' => now()->subYear()->addDays(10),
    ]);

    $this->child = createMonitoringChild($this->season, Child::STATUS_REJECTED, 'reset@example.com');
});

test('admin sees the reset button on a refused child of the current season', function () {
    $this->actingAs($this->admin);

    Livewire::test(ChildrenMonitoring::class)
        ->assertSeeHtml('Remettre à valider');
});

test('no reset button for children that are not refused', function () {
    $this->child->update(['status' => Child::STATUS_REJECTED_FINAL]);
    createMonitoringChild($this->season, Child::STATUS_VALIDATED, 'validated@example.com');
    $this->actingAs($this->admin);

    Livewire::test(ChildrenMonitoring::class)
        ->assertDontSeeHtml('Remettre à valider');
});

test('organizer does not see the reset button', function () {
    $this->actingAs($this->organizer);

    Livewire::test(ChildrenMonitoring::class)
        ->assertDontSeeHtml('Remettre à valider');
});

test('no reset button on a past season', function () {
    createMonitoringChild($this->pastSeason, Child::STATUS_REJECTED, 'past@example.com');
    $this->actingAs($this->admin);

    Livewire::test(ChildrenMonitoring::class)
        ->set('selectedSeasonId', $this->pastSeason->id)
        ->assertDontSeeHtml('Remettre à valider');
});

test('admin resets a refused child to pending and the action is logged', function () {
    $this->actingAs($this->admin);

    Livewire::test(ChildrenMonitoring::class)
        ->call('resetRejectedChild', $this->child->id);

    $child = $this->child->fresh();

    expect($child->status)->toBe(Child::STATUS_PENDING)
        ->and($child->rejection_comment)->toBeNull();

    $log = AdminActionLog::where('action_type', AdminActionLog::ACTION_CHILD_STATUS_RESET)->sole();

    expect($log->user_id)->toBe($this->admin->id)
        ->and($log->child_id)->toBe($this->child->id)
        ->and($log->gift_request_id)->toBe($this->child->gift_request_id)
        ->and($log->action_label)->toBe('Réinitialisation statut enfant');
});

test('reset ignores children that are not refused', function (string $status) {
    $this->child->update(['status' => $status]);
    $this->actingAs($this->admin);

    Livewire::test(ChildrenMonitoring::class)
        ->call('resetRejectedChild', $this->child->id);

    expect($this->child->fresh()->status)->toBe($status)
        ->and(AdminActionLog::count())->toBe(0);
})->with([
    Child::STATUS_REJECTED_FINAL,
    Child::STATUS_VALIDATED,
    Child::STATUS_PRINTED,
]);

test('reset ignores refused children of another season', function () {
    $pastChild = createMonitoringChild($this->pastSeason, Child::STATUS_REJECTED, 'past@example.com');
    $this->actingAs($this->admin);

    Livewire::test(ChildrenMonitoring::class)
        ->call('resetRejectedChild', $pastChild->id);

    expect($pastChild->fresh()->status)->toBe(Child::STATUS_REJECTED)
        ->and(AdminActionLog::count())->toBe(0);
});

test('organizer cannot reset a refused child', function () {
    $this->actingAs($this->organizer);

    Livewire::test(ChildrenMonitoring::class)
        ->call('resetRejectedChild', $this->child->id)
        ->assertForbidden();

    expect($this->child->fresh()->status)->toBe(Child::STATUS_REJECTED);
});

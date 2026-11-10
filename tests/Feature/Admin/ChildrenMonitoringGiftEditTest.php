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

beforeEach(function () {
    Role::firstOrCreate(['name' => Role::ADMIN]);
    Role::firstOrCreate(['name' => Role::ORGANIZER]);

    $this->admin = User::factory()->create(['name' => 'Alice Admin']);
    $this->admin->assignRole(Role::ADMIN);

    $this->organizer = User::factory()->create();
    $this->organizer->assignRole(Role::ORGANIZER);

    $season = Season::create([
        'name' => 'Noël 2026',
        'start_date' => now()->subDays(10),
        'end_date' => now()->addDays(10),
        'next_family_number' => 1,
    ]);

    $this->family = Family::create([
        'email' => 'gift-edit@example.com',
        'first_name' => 'Nina',
        'last_name' => 'Rossi',
        'street_name' => 'Rue Centrale',
        'house_no' => '10',
        'postal_code' => '1000',
        'city' => 'Lausanne',
        'phone' => '0791112233',
    ]);

    $giftRequest = GiftRequest::create([
        'family_id' => $this->family->id,
        'season_id' => $season->id,
        'status' => GiftRequest::STATUS_VALIDATED,
    ]);

    $this->child = Child::create([
        'gift_request_id' => $giftRequest->id,
        'first_name' => 'Lia',
        'gender' => Child::GENDER_GIRL,
        'birth_year' => now()->year - 6,
        'gift' => 'Peluche',
        'status' => Child::STATUS_VALIDATED,
    ]);
});

test('admin sees the gift edit button', function () {
    $this->actingAs($this->admin);

    Livewire::test(ChildrenMonitoring::class)
        ->assertSeeHtml('Modifier le cadeau');
});

test('organizer does not see the gift edit button', function () {
    $this->actingAs($this->organizer);

    Livewire::test(ChildrenMonitoring::class)
        ->assertDontSeeHtml('Modifier le cadeau');
});

test('admin can change a gift and the change is logged', function () {
    $this->actingAs($this->admin);

    Livewire::test(ChildrenMonitoring::class)
        ->call('editGift', $this->child->id)
        ->assertSet('editingGiftChildId', $this->child->id)
        ->assertSet('giftDraft', 'Peluche')
        ->set('giftDraft', '  Livre de contes  ')
        ->call('saveGift')
        ->assertHasNoErrors()
        ->assertSet('editingGiftChildId', null);

    expect($this->child->fresh()->gift)->toBe('Livre de contes');

    $log = AdminActionLog::where('action_type', AdminActionLog::ACTION_GIFT_UPDATED)->sole();

    expect($log->user_id)->toBe($this->admin->id)
        ->and($log->user_label)->toBe('Alice Admin')
        ->and($log->child_id)->toBe($this->child->id)
        ->and($log->family_id)->toBe($this->family->id)
        ->and($log->description)->toContain('« Peluche »')
        ->and($log->description)->toContain('« Livre de contes »')
        ->and($log->action_label)->toBe('Modification cadeau');
});

test('cancel leaves the gift unchanged and writes no log', function () {
    $this->actingAs($this->admin);

    Livewire::test(ChildrenMonitoring::class)
        ->call('editGift', $this->child->id)
        ->set('giftDraft', 'Vélo')
        ->call('cancelGiftEdit')
        ->assertSet('editingGiftChildId', null);

    expect($this->child->fresh()->gift)->toBe('Peluche');
    expect(AdminActionLog::count())->toBe(0);
});

test('saving the same gift writes no log', function () {
    $this->actingAs($this->admin);

    Livewire::test(ChildrenMonitoring::class)
        ->call('editGift', $this->child->id)
        ->call('saveGift')
        ->assertHasNoErrors();

    expect(AdminActionLog::count())->toBe(0);
});

test('an empty gift is refused', function () {
    $this->actingAs($this->admin);

    Livewire::test(ChildrenMonitoring::class)
        ->call('editGift', $this->child->id)
        ->set('giftDraft', '   ')
        ->call('saveGift')
        ->assertHasErrors(['giftDraft' => 'required']);

    expect($this->child->fresh()->gift)->toBe('Peluche');
});

test('organizer cannot edit a gift', function () {
    $this->actingAs($this->organizer);

    Livewire::test(ChildrenMonitoring::class)
        ->call('editGift', $this->child->id)
        ->assertForbidden();

    Livewire::test(ChildrenMonitoring::class)
        ->set('giftDraft', 'Vélo')
        ->call('saveGift')
        ->assertForbidden();

    expect($this->child->fresh()->gift)->toBe('Peluche');
});

test('gift update appears in the admin action log page', function () {
    AdminActionLog::create([
        'user_id' => $this->admin->id,
        'user_label' => $this->admin->name,
        'action_type' => AdminActionLog::ACTION_GIFT_UPDATED,
        'description' => 'Cadeau modifié pour Lia : « Peluche » → « Vélo »',
        'child_id' => $this->child->id,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.admin-action-logs'))
        ->assertOk()
        ->assertSee('Modification cadeau')
        ->assertSee('« Peluche » → « Vélo »');
});

test('gift column shows height and shoe size when set', function () {
    $this->child->update(['height' => 120, 'shoe_size' => '32']);
    $this->actingAs($this->admin);

    Livewire::test(ChildrenMonitoring::class)
        ->assertSeeHtml('<span class="gift-sizes">120 cm / P. 32</span>');
});

test('gift column shows no sizes when neither is set', function () {
    $this->actingAs($this->admin);

    Livewire::test(ChildrenMonitoring::class)
        ->assertDontSeeHtml('gift-sizes');
});

test('sizes summary combines available sizes', function () {
    expect(new Child(['height' => 120]))->sizes_summary->toBe('120 cm')
        ->and(new Child(['shoe_size' => '32']))->sizes_summary->toBe('P. 32')
        ->and(new Child(['height' => 120, 'shoe_size' => '32']))->sizes_summary->toBe('120 cm / P. 32')
        ->and(new Child)->sizes_summary->toBeNull();
});

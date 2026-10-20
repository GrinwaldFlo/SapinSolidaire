<?php

use App\Livewire\Admin\DevTools;
use App\Models\Child;
use App\Models\EmailToken;
use App\Models\Family;
use App\Models\GiftRequest;
use App\Models\Role;
use App\Models\Season;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');

    Role::firstOrCreate(['name' => Role::ADMIN]);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(Role::ADMIN);

    $this->season = Season::create([
        'name' => 'Noël 2026',
        'start_date' => now()->subDays(10),
        'end_date' => now()->addDays(20),
        'next_family_number' => 1,
    ]);

    $this->previousSeason = Season::create([
        'name' => 'Noël 2025',
        'start_date' => now()->subYear()->subDays(10),
        'end_date' => now()->subYear()->addDays(20),
        'next_family_number' => 1,
    ]);

    $this->createFamily = function (string $email): Family {
        return Family::create([
            'email' => $email,
            'first_name' => 'Paul',
            'last_name' => 'Martin',
            'street_name' => 'Rue Centrale',
            'house_no' => '8',
            'postal_code' => '1000',
            'city' => 'Lausanne',
            'phone' => '0791234567',
        ]);
    };

    $this->createRequestWithChild = function (Family $family, Season $season, ?string $proofPath = null): GiftRequest {
        $request = GiftRequest::create([
            'family_id' => $family->id,
            'season_id' => $season->id,
            'status' => GiftRequest::STATUS_PENDING,
            'proof_of_habitation_path' => $proofPath,
        ]);

        Child::create([
            'gift_request_id' => $request->id,
            'first_name' => 'Alice',
            'gender' => Child::GENDER_GIRL,
            'birth_year' => 2019,
            'gift' => 'Poupée',
            'status' => Child::STATUS_PENDING,
        ]);

        return $request;
    };

    $this->family = ($this->createFamily)('to-delete@example.com');
    $this->otherFamily = ($this->createFamily)('keep@example.com');

    Storage::disk('local')->put('proofs/to-delete.pdf', 'proof');
    Storage::disk('local')->put('proofs/keep.pdf', 'proof');

    ($this->createRequestWithChild)($this->family, $this->season, 'proofs/to-delete.pdf');
    ($this->createRequestWithChild)($this->family, $this->previousSeason);
    ($this->createRequestWithChild)($this->otherFamily, $this->season, 'proofs/keep.pdf');

    EmailToken::createForEmail($this->family->email);
    EmailToken::createForEmail($this->otherFamily->email);

    $this->actingAs($this->admin);
});

test('searching a family by email selects it for deletion', function () {
    Livewire::test(DevTools::class)
        ->set('deleteFamilyEmail', 'to-delete@example.com')
        ->call('searchFamilyToDelete')
        ->assertSet('familyToDeleteId', $this->family->id)
        ->assertSee('to-delete@example.com')
        ->assertSee('2 enfant(s)');
});

test('searching an unknown email shows an error and selects nothing', function () {
    Livewire::test(DevTools::class)
        ->set('deleteFamilyEmail', 'unknown@example.com')
        ->call('searchFamilyToDelete')
        ->assertSet('familyToDeleteId', null)
        ->assertSet('flashType', 'error');
});

test('search requires a valid email', function () {
    Livewire::test(DevTools::class)
        ->set('deleteFamilyEmail', 'not-an-email')
        ->call('searchFamilyToDelete')
        ->assertHasErrors(['deleteFamilyEmail' => 'email']);
});

test('deleting a family removes its requests, children, tokens and proof files across seasons', function () {
    Livewire::test(DevTools::class)
        ->set('deleteFamilyEmail', 'to-delete@example.com')
        ->call('searchFamilyToDelete')
        ->call('deleteFamily')
        ->assertSet('flashType', 'success')
        ->assertSet('familyToDeleteId', null)
        ->assertSet('deleteFamilyEmail', '');

    expect(Family::find($this->family->id))->toBeNull()
        ->and(GiftRequest::where('family_id', $this->family->id)->count())->toBe(0)
        ->and(EmailToken::where('email', 'to-delete@example.com')->count())->toBe(0)
        ->and(Child::count())->toBe(1);

    Storage::disk('local')->assertMissing('proofs/to-delete.pdf');
});

test('deleting a family keeps other families untouched', function () {
    Livewire::test(DevTools::class)
        ->set('deleteFamilyEmail', 'to-delete@example.com')
        ->call('searchFamilyToDelete')
        ->call('deleteFamily');

    expect(Family::find($this->otherFamily->id))->not->toBeNull()
        ->and(GiftRequest::where('family_id', $this->otherFamily->id)->count())->toBe(1)
        ->and(EmailToken::where('email', 'keep@example.com')->count())->toBe(1);

    Storage::disk('local')->assertExists('proofs/keep.pdf');
});

test('cancelling clears the selected family without deleting it', function () {
    Livewire::test(DevTools::class)
        ->set('deleteFamilyEmail', 'to-delete@example.com')
        ->call('searchFamilyToDelete')
        ->call('cancelFamilyDeletion')
        ->assertSet('familyToDeleteId', null);

    expect(Family::find($this->family->id))->not->toBeNull();
});

test('deleting without a selected family shows an error', function () {
    Livewire::test(DevTools::class)
        ->call('deleteFamily')
        ->assertSet('flashType', 'error');

    expect(Family::count())->toBe(2);
});

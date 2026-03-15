<?php

use App\Livewire\Family\GiftRequestForm;
use App\Models\EmailToken;
use App\Models\Family;
use App\Models\GiftRequest;
use App\Models\Season;
use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Setting::clearCache();
    Storage::fake('local');

    // Create an active season
    $this->season = Season::create([
        'name' => 'Test Season',
        'start_date' => now()->subDay(),
        'end_date' => now()->addMonth(),
        'modification_deadline' => now()->addMonth(),
    ]);

    // Create a valid email token
    $this->emailToken = EmailToken::createForEmail('test@example.com');
});

// --- Setting loaded on mount ---

test('proofOfHabitationEnabled is loaded from settings on mount', function () {
    Setting::setValue(Setting::PROOF_OF_HABITATION_ENABLED, '1');

    Livewire::test(GiftRequestForm::class, ['token' => $this->emailToken->token])
        ->assertSet('proofOfHabitationEnabled', true);
});

test('proofOfHabitationEnabled is false by default', function () {
    Livewire::test(GiftRequestForm::class, ['token' => $this->emailToken->token])
        ->assertSet('proofOfHabitationEnabled', false);
});

// --- Submit validation ---

test('submit requires proof of habitation when feature is enabled', function () {
    Setting::setValue(Setting::PROOF_OF_HABITATION_ENABLED, '1');

    Livewire::test(GiftRequestForm::class, ['token' => $this->emailToken->token])
        ->call('acceptConsecutiveYears')
        ->call('acceptCity')
        ->set('firstName', 'Jean')
        ->set('lastName', 'Dupont')
        ->set('address', 'Rue de Test 1')
        ->set('postalCode', '1000')
        ->set('city', 'Lausanne')
        ->set('phone', '+41791234567')
        ->set('children.0.first_name', 'Petit')
        ->set('children.0.birth_year', '2018')
        ->set('children.0.gift', 'Livre')
        ->call('submit')
        ->assertHasErrors('proofOfHabitation');
});

test('submit does not require proof of habitation when feature is disabled', function () {
    // Feature disabled (default)

    Livewire::test(GiftRequestForm::class, ['token' => $this->emailToken->token])
        ->call('acceptConsecutiveYears')
        ->call('acceptCity')
        ->set('firstName', 'Jean')
        ->set('lastName', 'Dupont')
        ->set('address', 'Rue de Test 1')
        ->set('postalCode', '1000')
        ->set('city', 'Lausanne')
        ->set('phone', '+41791234567')
        ->set('children.0.first_name', 'Petit')
        ->set('children.0.birth_year', '2018')
        ->set('children.0.gift', 'Livre')
        ->call('submit')
        ->assertHasNoErrors('proofOfHabitation');
});

test('submit succeeds with proof of habitation when feature is enabled', function () {
    Setting::setValue(Setting::PROOF_OF_HABITATION_ENABLED, '1');

    $file = UploadedFile::fake()->create('proof.jpg', 100, 'image/jpeg');

    Livewire::test(GiftRequestForm::class, ['token' => $this->emailToken->token])
        ->call('acceptConsecutiveYears')
        ->call('acceptCity')
        ->set('firstName', 'Jean')
        ->set('lastName', 'Dupont')
        ->set('address', 'Rue de Test 1')
        ->set('postalCode', '1000')
        ->set('city', 'Lausanne')
        ->set('phone', '+41791234567')
        ->set('proofOfHabitation', $file)
        ->set('children.0.first_name', 'Petit')
        ->set('children.0.birth_year', '2018')
        ->set('children.0.gift', 'Livre')
        ->call('submit')
        ->assertHasNoErrors('proofOfHabitation')
        ->assertSet('submitted', true);
});

test('proof of habitation file is stored on submit', function () {
    Setting::setValue(Setting::PROOF_OF_HABITATION_ENABLED, '1');

    $file = UploadedFile::fake()->create('proof.jpg', 100, 'image/jpeg');

    Livewire::test(GiftRequestForm::class, ['token' => $this->emailToken->token])
        ->call('acceptConsecutiveYears')
        ->call('acceptCity')
        ->set('firstName', 'Jean')
        ->set('lastName', 'Dupont')
        ->set('address', 'Rue de Test 1')
        ->set('postalCode', '1000')
        ->set('city', 'Lausanne')
        ->set('phone', '+41791234567')
        ->set('proofOfHabitation', $file)
        ->set('children.0.first_name', 'Petit')
        ->set('children.0.birth_year', '2018')
        ->set('children.0.gift', 'Livre')
        ->call('submit');

    $family = Family::where('email', 'test@example.com')->first();
    $giftRequest = $family->getRequestForSeason($this->season);

    expect($giftRequest->proof_of_habitation_path)->not->toBeNull();
    Storage::disk('local')->assertExists($giftRequest->proof_of_habitation_path);
});

test('submit does not require new upload when proof already exists', function () {
    Setting::setValue(Setting::PROOF_OF_HABITATION_ENABLED, '1');

    // Create family with existing request that has a proof
    $family = Family::create([
        'email' => 'test@example.com',
        'first_name' => 'Jean',
        'last_name' => 'Dupont',
        'address' => 'Rue de Test 1',
        'postal_code' => '1000',
        'city' => 'Lausanne',
        'phone' => '+41791234567',
    ]);

    $giftRequest = GiftRequest::create([
        'family_id' => $family->id,
        'season_id' => $this->season->id,
        'status' => GiftRequest::STATUS_PENDING,
        'status_changed_at' => now(),
        'proof_of_habitation_path' => 'proof-of-habitation/existing.jpg',
    ]);

    // Store a fake file at the existing path
    Storage::disk('local')->put('proof-of-habitation/existing.jpg', 'fake-image');

    Livewire::test(GiftRequestForm::class, ['token' => $this->emailToken->token])
        ->assertSet('existingProofPath', 'proof-of-habitation/existing.jpg')
        ->set('firstName', 'Jean')
        ->set('lastName', 'Dupont')
        ->set('address', 'Rue de Test 1')
        ->set('postalCode', '1000')
        ->set('city', 'Lausanne')
        ->set('phone', '+41791234567')
        ->set('children.0.first_name', 'Petit')
        ->set('children.0.birth_year', '2018')
        ->set('children.0.gift', 'Livre')
        ->call('submit')
        ->assertHasNoErrors('proofOfHabitation');
});

test('re-uploading proof deletes the old file', function () {
    Setting::setValue(Setting::PROOF_OF_HABITATION_ENABLED, '1');

    // Create family with existing request that has a proof
    $family = Family::create([
        'email' => 'test@example.com',
        'first_name' => 'Jean',
        'last_name' => 'Dupont',
        'address' => 'Rue de Test 1',
        'postal_code' => '1000',
        'city' => 'Lausanne',
        'phone' => '+41791234567',
    ]);

    $giftRequest = GiftRequest::create([
        'family_id' => $family->id,
        'season_id' => $this->season->id,
        'status' => GiftRequest::STATUS_PENDING,
        'status_changed_at' => now(),
        'proof_of_habitation_path' => 'proof-of-habitation/old-proof.jpg',
    ]);

    // Store a fake file at the existing path
    Storage::disk('local')->put('proof-of-habitation/old-proof.jpg', 'old-image');

    $newFile = UploadedFile::fake()->create('new-proof.jpg', 100, 'image/jpeg');

    Livewire::test(GiftRequestForm::class, ['token' => $this->emailToken->token])
        ->set('proofOfHabitation', $newFile)
        ->set('firstName', 'Jean')
        ->set('lastName', 'Dupont')
        ->set('address', 'Rue de Test 1')
        ->set('postalCode', '1000')
        ->set('city', 'Lausanne')
        ->set('phone', '+41791234567')
        ->set('children.0.first_name', 'Petit')
        ->set('children.0.birth_year', '2018')
        ->set('children.0.gift', 'Livre')
        ->call('submit')
        ->assertSet('submitted', true);

    // Old file should be deleted
    Storage::disk('local')->assertMissing('proof-of-habitation/old-proof.jpg');

    // New file should exist
    $giftRequest->refresh();
    expect($giftRequest->proof_of_habitation_path)->not->toBe('proof-of-habitation/old-proof.jpg');
    Storage::disk('local')->assertExists($giftRequest->proof_of_habitation_path);
});

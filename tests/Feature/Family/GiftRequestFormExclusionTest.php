<?php

use App\Livewire\Family\GiftRequestForm;
use App\Models\EmailToken;
use App\Models\Family;
use App\Models\GiftRequest;
use App\Models\Season;
use App\Models\Setting;
use App\Services\CustomText;
use App\Services\FamilyDuplicateService;
use Livewire\Livewire;

beforeEach(function () {
    Setting::clearCache();

    $this->pastSeason = Season::create([
        'name' => 'Past Season',
        'start_date' => now()->subYear()->subDay(),
        'end_date' => now()->subYear()->addMonth(),
    ]);

    $this->season = Season::create([
        'name' => 'Active Season',
        'start_date' => now()->subDay(),
        'end_date' => now()->addMonth(),
        'modification_deadline' => now()->addMonth(),
    ]);

    $this->family = Family::create([
        'email' => 'excluded@example.com',
        'first_name' => 'Jean',
        'last_name' => 'Dupont',
        'street_name' => 'Rue du Lac',
        'house_no' => '1',
        'postal_code' => '1400',
        'city' => 'Yverdon-les-Bains',
        'phone' => '0791234567',
    ]);

    $this->emailToken = EmailToken::createForEmail('excluded@example.com');
});

test('final rejection of a request excludes the family', function () {
    $request = GiftRequest::create([
        'family_id' => $this->family->id,
        'season_id' => $this->pastSeason->id,
        'status' => GiftRequest::STATUS_PENDING,
    ]);

    $request->setStatus(GiftRequest::STATUS_REJECTED_FINAL, 'Refus définitif');

    $this->family->refresh();
    expect($this->family->isExcluded())->toBeTrue();
    expect($this->family->exclusion_reason)->toBe('Refus définitif');
});

test('family rejected final in a past season cannot request in the next season', function () {
    GiftRequest::create([
        'family_id' => $this->family->id,
        'season_id' => $this->pastSeason->id,
        'status' => GiftRequest::STATUS_PENDING,
    ])->setStatus(GiftRequest::STATUS_REJECTED_FINAL, 'Refus définitif');

    Livewire::test(GiftRequestForm::class, ['token' => $this->emailToken->token])
        ->assertSet('isPermanentlyRejected', true)
        ->assertSet('rejectionComment', 'Refus définitif');
});

test('family excluded without any request cannot request in the active season', function () {
    $this->family->exclude('Exclue par admin');

    Livewire::test(GiftRequestForm::class, ['token' => $this->emailToken->token])
        ->assertSet('isPermanentlyRejected', true)
        ->assertSet('rejectionComment', 'Exclue par admin')
        ->assertSee(CustomText::get(CustomText::FORM_PERMANENTLY_REJECTED));
});

test('submit is refused when the family is excluded after the form was opened', function () {
    $component = Livewire::test(GiftRequestForm::class, ['token' => $this->emailToken->token])
        ->assertSet('isPermanentlyRejected', false);

    $this->family->exclude('Exclue pendant la saisie');

    $component->call('submit')
        ->assertSet('isPermanentlyRejected', true);

    expect(GiftRequest::where('family_id', $this->family->id)->exists())->toBeFalse();
});

test('permanent rejection flag cannot be changed from the client', function () {
    $this->family->exclude('Motif');

    Livewire::test(GiftRequestForm::class, ['token' => $this->emailToken->token])
        ->set('isPermanentlyRejected', false);
})->throws(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);

test('merging duplicates keeps the exclusion of the removed family', function () {
    $this->family->exclude('Motif');

    $keep = Family::create([
        'email' => 'keep@example.com',
        'first_name' => 'Jean',
        'last_name' => 'Dupont',
        'street_name' => 'Rue du Lac',
        'house_no' => '1',
        'postal_code' => '1400',
        'city' => 'Yverdon-les-Bains',
    ]);

    app(FamilyDuplicateService::class)->merge($keep, $this->family);

    $keep->refresh();
    expect($keep->isExcluded())->toBeTrue();
    expect($keep->exclusion_reason)->toBe('Motif');
});

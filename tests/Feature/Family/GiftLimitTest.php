<?php

use App\Livewire\Admin\SeasonManagement;
use App\Livewire\Family\GiftRequestForm;
use App\Livewire\Family\Home;
use App\Models\Child;
use App\Models\EmailToken;
use App\Models\Family;
use App\Models\GiftRequest;
use App\Models\Role;
use App\Models\Season;
use App\Models\Setting;
use App\Models\User;
use App\Services\CustomText;
use Livewire\Livewire;

beforeEach(function () {
    Setting::clearCache();

    $this->season = Season::create([
        'name' => 'Active Season',
        'start_date' => now()->subDays(10),
        'end_date' => now()->addMonth(),
        'modification_deadline' => now()->addWeek(),
        'max_gifts' => 2,
    ]);

    $this->family = Family::create([
        'email' => 'registered@example.com',
        'first_name' => 'Jean',
        'last_name' => 'Dupont',
        'street_name' => 'Rue du Lac',
        'house_no' => '1',
        'postal_code' => '1400',
        'city' => 'Yverdon-les-Bains',
        'phone' => '0791234567',
    ]);

    $this->giftRequest = GiftRequest::create([
        'family_id' => $this->family->id,
        'season_id' => $this->season->id,
        'status' => GiftRequest::STATUS_PENDING,
    ]);

    $this->registeredToken = EmailToken::createForEmail('registered@example.com');
    $this->newToken = EmailToken::createForEmail('new@example.com');
});

function addGift(GiftRequest $giftRequest, string $status = Child::STATUS_PENDING, string $firstName = 'Léa'): Child
{
    return Child::create([
        'gift_request_id' => $giftRequest->id,
        'first_name' => $firstName,
        'gender' => Child::GENDER_GIRL,
        'birth_year' => (int) date('Y') - 5,
        'gift' => 'Poupée',
        'status' => $status,
    ]);
}

function newChildData(): array
{
    return [
        'id' => null,
        'first_name' => 'Hugo',
        'gender' => Child::GENDER_BOY,
        'anonymous' => false,
        'birth_year' => (int) date('Y') - 4,
        'height' => '',
        'gift' => 'Ballon',
        'shoe_size' => '',
        'status' => Child::STATUS_PENDING,
        'can_modify' => true,
    ];
}

test('gifts are counted in every status except rejected ones', function () {
    foreach ([
        Child::STATUS_PENDING,
        Child::STATUS_VALIDATED,
        Child::STATUS_PRINTED,
        Child::STATUS_RECEIVED,
        Child::STATUS_GIVEN,
        Child::STATUS_REJECTED,
        Child::STATUS_REJECTED_FINAL,
    ] as $index => $status) {
        addGift($this->giftRequest, $status, "Enfant {$index}");
    }

    expect($this->season->countedGiftsCount())->toBe(5);
});

test('gifts of other seasons are not counted', function () {
    $previous = Season::create([
        'name' => 'Previous Season',
        'start_date' => now()->subYear()->subMonth(),
        'end_date' => now()->subYear(),
    ]);
    addGift(GiftRequest::create([
        'family_id' => $this->family->id,
        'season_id' => $previous->id,
        'status' => GiftRequest::STATUS_VALIDATED,
    ]));
    addGift($this->giftRequest);

    expect($this->season->countedGiftsCount())->toBe(1)
        ->and($this->season->hasReachedMaxGifts())->toBeFalse();
});

test('a season without gift limit is never full', function () {
    $this->season->update(['max_gifts' => null]);
    addGift($this->giftRequest, Child::STATUS_PENDING, 'Léa');
    addGift($this->giftRequest, Child::STATUS_PENDING, 'Tom');
    addGift($this->giftRequest, Child::STATUS_PENDING, 'Zoé');

    expect($this->season->hasReachedMaxGifts())->toBeFalse();
});

test('rejected gifts do not reach the limit', function () {
    addGift($this->giftRequest, Child::STATUS_PENDING, 'Léa');
    addGift($this->giftRequest, Child::STATUS_REJECTED, 'Tom');
    addGift($this->giftRequest, Child::STATUS_REJECTED_FINAL, 'Zoé');

    expect($this->season->hasReachedMaxGifts())->toBeFalse();

    Livewire::test(GiftRequestForm::class, ['token' => $this->newToken->token])
        ->assertSet('canModify', true)
        ->assertSet('registrationClosedMessage', null);
});

test('home page shows the gift limit message when the limit is reached', function () {
    addGift($this->giftRequest, Child::STATUS_PENDING, 'Léa');
    addGift($this->giftRequest, Child::STATUS_VALIDATED, 'Tom');

    Livewire::test(Home::class)
        ->assertSet('registrationClosedMessage', CustomText::default(CustomText::REGISTRATION_GIFT_LIMIT_REACHED))
        ->assertSee('Inscriptions closes');
});

test('the gift limit message can be customized', function () {
    CustomText::set(CustomText::REGISTRATION_GIFT_LIMIT_REACHED, 'Plus de cadeaux disponibles.');
    addGift($this->giftRequest, Child::STATUS_PENDING, 'Léa');
    addGift($this->giftRequest, Child::STATUS_PENDING, 'Tom');

    Livewire::test(Home::class)
        ->assertSet('registrationClosedMessage', 'Plus de cadeaux disponibles.');
});

test('new family cannot register when the gift limit is reached', function () {
    addGift($this->giftRequest, Child::STATUS_PENDING, 'Léa');
    addGift($this->giftRequest, Child::STATUS_PENDING, 'Tom');

    Livewire::test(GiftRequestForm::class, ['token' => $this->newToken->token])
        ->assertSet('canModify', false)
        ->assertSet('registrationClosedMessage', CustomText::default(CustomText::REGISTRATION_GIFT_LIMIT_REACHED))
        ->assertSee('Inscriptions closes');
});

test('new family submit is refused when the gift limit is reached after the form was opened', function () {
    addGift($this->giftRequest, Child::STATUS_PENDING, 'Léa');

    $component = Livewire::test(GiftRequestForm::class, ['token' => $this->newToken->token])
        ->assertSet('canModify', true);

    addGift($this->giftRequest, Child::STATUS_PENDING, 'Tom');

    $component->call('submit')
        ->assertSet('canModify', false)
        ->assertSet('submitted', false);

    expect(Family::where('email', 'new@example.com')->exists())->toBeFalse();
});

test('registered family can still edit its request but cannot add children when the limit is reached', function () {
    addGift($this->giftRequest, Child::STATUS_PENDING, 'Léa');
    addGift($this->giftRequest, Child::STATUS_PENDING, 'Tom');

    Livewire::test(GiftRequestForm::class, ['token' => $this->registeredToken->token])
        ->assertSet('canModify', true)
        ->assertSet('giftLimitMessage', CustomText::default(CustomText::REGISTRATION_GIFT_LIMIT_REACHED))
        ->assertSee('Nombre maximum de cadeaux atteint')
        ->assertDontSee('Ajouter un enfant')
        ->call('addChild')
        ->assertCount('children', 2);
});

test('registered family can modify existing children when the limit is reached', function () {
    addGift($this->giftRequest, Child::STATUS_PENDING, 'Léa');
    $tom = addGift($this->giftRequest, Child::STATUS_REJECTED, 'Tom');
    addGift(GiftRequest::create([
        'family_id' => Family::create(['email' => 'other@example.com'])->id,
        'season_id' => $this->season->id,
        'status' => GiftRequest::STATUS_PENDING,
    ]), Child::STATUS_PENDING, 'Zoé');

    Livewire::test(GiftRequestForm::class, ['token' => $this->registeredToken->token])
        ->assertSet('giftLimitMessage', CustomText::default(CustomText::REGISTRATION_GIFT_LIMIT_REACHED))
        ->set('children.1.gift', 'Vélo')
        ->call('submit')
        ->assertSet('submitted', true);

    expect($tom->fresh())
        ->gift->toBe('Vélo')
        ->status->toBe(Child::STATUS_PENDING);
});

test('registered family submit with a new child is refused when the limit is reached', function () {
    addGift($this->giftRequest, Child::STATUS_PENDING, 'Léa');

    $component = Livewire::test(GiftRequestForm::class, ['token' => $this->registeredToken->token])
        ->assertSet('giftLimitMessage', null)
        ->call('addChild')
        ->set('children.1', newChildData());

    addGift(GiftRequest::create([
        'family_id' => Family::create(['email' => 'other@example.com'])->id,
        'season_id' => $this->season->id,
        'status' => GiftRequest::STATUS_PENDING,
    ]), Child::STATUS_PENDING, 'Zoé');

    $component->call('submit')
        ->assertSet('submitted', false)
        ->assertSet('giftLimitMessage', CustomText::default(CustomText::REGISTRATION_GIFT_LIMIT_REACHED))
        ->assertSee('Retirez les enfants ajoutés');

    expect($this->giftRequest->children()->count())->toBe(1);
});

test('submission going over the limit is accepted when the limit was not reached yet', function () {
    addGift($this->giftRequest, Child::STATUS_PENDING, 'Léa');

    Livewire::test(GiftRequestForm::class, ['token' => $this->registeredToken->token])
        ->call('addChild')
        ->set('children.1', newChildData())
        ->call('addChild')
        ->set('children.2', array_merge(newChildData(), ['first_name' => 'Emma', 'gender' => Child::GENDER_GIRL]))
        ->call('submit')
        ->assertSet('submitted', true);

    expect($this->season->countedGiftsCount())->toBe(3);
});

test('gift limit message does not replace the manual block message', function () {
    Setting::setValue(Setting::REGISTRATION_BLOCKED, '1');
    Setting::setValue(Setting::REGISTRATION_BLOCKED_REASON, 'Fermé');
    addGift($this->giftRequest, Child::STATUS_PENDING, 'Léa');
    addGift($this->giftRequest, Child::STATUS_PENDING, 'Tom');

    Livewire::test(GiftRequestForm::class, ['token' => $this->registeredToken->token])
        ->assertSet('canModify', false)
        ->assertSet('registrationClosedMessage', 'Fermé')
        ->assertSet('giftLimitMessage', null);
});

test('gift limit cannot be changed from the client', function () {
    Livewire::test(GiftRequestForm::class, ['token' => $this->registeredToken->token])
        ->set('giftLimitMessage', null);
})->throws(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);

test('admin can set the maximum number of gifts of a season', function () {
    Role::firstOrCreate(['name' => Role::ADMIN]);
    $admin = User::factory()->create();
    $admin->assignRole(Role::ADMIN);
    $this->actingAs($admin);

    Livewire::test(SeasonManagement::class)
        ->call('edit', $this->season->id)
        ->assertSet('maxGifts', 2)
        ->assertSee('Nombre de cadeaux maximum')
        ->set('maxGifts', 150)
        ->call('save')
        ->assertHasNoErrors();

    expect($this->season->fresh()->max_gifts)->toBe(150);

    Livewire::test(SeasonManagement::class)
        ->call('edit', $this->season->id)
        ->set('maxGifts', null)
        ->call('save');

    expect($this->season->fresh()->max_gifts)->toBeNull();

    Livewire::test(SeasonManagement::class)
        ->call('edit', $this->season->id)
        ->set('maxGifts', 0)
        ->call('save')
        ->assertHasErrors(['maxGifts' => 'min']);
});

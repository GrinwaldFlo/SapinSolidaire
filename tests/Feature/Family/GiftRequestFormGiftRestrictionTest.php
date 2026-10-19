<?php

use App\Livewire\Family\GiftRequestForm;
use App\Models\EmailToken;
use App\Models\PickupSlot;
use App\Models\Season;
use App\Models\Setting;
use Livewire\Livewire;

beforeEach(function () {
    Setting::clearCache();

    $this->season = Season::create([
        'name' => 'Test Season',
        'start_date' => now()->subDay(),
        'end_date' => now()->addMonth(),
        'modification_deadline' => now()->addMonth(),
    ]);

    PickupSlot::create([
        'season_id' => $this->season->id,
        'start_datetime' => now()->setDate(2026, 12, 23)->setTime(14, 0),
        'end_datetime' => now()->setDate(2026, 12, 23)->setTime(18, 0),
    ]);

    $this->emailToken = EmailToken::createForEmail('test@example.com');
});

function giftErrorFor(string $gift): ?string
{
    $errors = Livewire::test(GiftRequestForm::class, ['token' => test()->emailToken->token])
        ->set('children.0.gift', $gift)
        ->call('validateChild', 0)
        ->get('fieldErrors');

    return $errors['children.0.gift'][0] ?? null;
}

test('forbidden gift is rejected regardless of accents and case', function (string $gift) {
    Setting::setValue(Setting::GIFT_RESTRICTIONS, 'console');

    expect(giftErrorFor($gift))->toBe('Ce type de cadeau n\'est pas autorisé.');
})->with([
    'exact' => 'console',
    'uppercase' => 'CONSOLE de jeu',
    'accented gift' => 'Cônsole',
]);

test('accented forbidden keyword matches unaccented gift', function () {
    Setting::setValue(Setting::GIFT_RESTRICTIONS, 'pétard');

    expect(giftErrorFor('Petards'))->toBe('Ce type de cadeau n\'est pas autorisé.');
});

test('gift not matching any restriction is accepted', function () {
    Setting::setValue(Setting::GIFT_RESTRICTIONS, "console\npétard");

    expect(giftErrorFor('Puzzle'))->toBeNull();
});

test('gift is validated while typing without calling validateChild', function () {
    Setting::setValue(Setting::GIFT_RESTRICTIONS, 'console');

    $component = Livewire::test(GiftRequestForm::class, ['token' => test()->emailToken->token])
        ->set('children.0.gift', 'Console de jeu');

    expect($component->get('fieldErrors')['children.0.gift'][0] ?? null)
        ->toBe('Ce type de cadeau n\'est pas autorisé.')
        ->and($component->get('touchedFields'))->toContain('children.0.gift');

    $component->set('children.0.gift', 'Puzzle');

    expect($component->get('fieldErrors')['children.0.gift'] ?? null)->toBeNull();
});

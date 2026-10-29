<?php

use App\Livewire\Admin\SeasonManagement;
use App\Mail\GiftReceivedMail;
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
    $this->actingAs($this->admin);
});

function fillSeasonForm($component, string $phone)
{
    return $component
        ->set('name', 'Noël 2026')
        ->set('startDate', '2026-11-01')
        ->set('endDate', '2026-12-31')
        ->set('responsiblePhone', $phone);
}

test('responsible phone accepts a label with the number', function () {
    fillSeasonForm(Livewire::test(SeasonManagement::class), 'Réception 076 455 55 55 (lundi-vendredi, 9h-17h)')
        ->call('save')
        ->assertHasNoErrors();

    expect(Season::first()->responsible_phone)->toBe('Réception 076 455 55 55 (lundi-vendredi, 9h-17h)');
});

test('responsible phone is limited to 100 characters', function () {
    fillSeasonForm(Livewire::test(SeasonManagement::class), str_repeat('1', 101))
        ->call('save')
        ->assertHasErrors(['responsiblePhone' => 'max']);
});

test('gift received mail shows the full responsible phone text', function () {
    $giftRequest = new GiftRequest([
        'slot_start_datetime' => now()->addDay()->setTime(10, 0),
        'slot_end_datetime' => now()->addDay()->setTime(11, 0),
    ]);
    $giftRequest->setRelation('family', new Family(['last_name' => 'Martin']));

    $season = new Season([
        'pickup_address' => 'Rue de Test 1, 1000 Lausanne',
        'responsible_phone' => 'Réception 076 455 55 55',
    ]);

    $mail = new GiftReceivedMail($giftRequest, $season);

    $mail->assertSeeInHtml('Réception 076 455 55 55', false)
        ->assertDontSeeInHtml('Téléphone :', false);
    $mail->assertSeeInText('Réception 076 455 55 55')
        ->assertDontSeeInText('Téléphone :');
});

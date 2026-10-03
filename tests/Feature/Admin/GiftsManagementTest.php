<?php

use App\Livewire\Admin\GiftsManagement;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    Setting::clearCache();

    Role::firstOrCreate(['name' => Role::ADMIN]);
    $this->admin = User::factory()->create();
    $this->admin->assignRole(Role::ADMIN);
});

test('gifts management page loads configured values', function () {
    Setting::setValue(Setting::GIFT_SUGGESTIONS, "Livre\nJeu");
    Setting::setValue(Setting::GIFT_RESTRICTIONS, "arme");
    Setting::setValue(Setting::GIFTS_WITH_SHOE_SIZE, "basket");
    Setting::setValue(Setting::GIFTS_WITH_SIZE, "veste");

    $this->actingAs($this->admin);

    Livewire::test(GiftsManagement::class)
        ->assertSet('giftSuggestions', "Livre\nJeu")
        ->assertSet('giftRestrictions', 'arme')
        ->assertSet('giftsWithShoeSize', 'basket')
        ->assertSet('giftsWithSize', 'veste');
});

test('admin can save gifts management settings', function () {
    $this->actingAs($this->admin);

    Livewire::test(GiftsManagement::class)
        ->set('giftSuggestions', "Livre\nPeluche")
        ->set('giftRestrictions', "arme\ncouteau")
        ->set('giftsWithShoeSize', "basket\npatin")
        ->set('giftsWithSize', "veste\npantalon")
        ->call('save')
        ->assertHasNoErrors()
        ->assertSessionHas('message', 'Paramètres cadeaux enregistrés avec succès.')
        ->assertRedirect(route('admin.dashboard'));

    Setting::clearCache();

    expect(Setting::getGiftSuggestions())->toBe(['Livre', 'Peluche']);
    expect(Setting::getGiftRestrictions())->toBe(['arme', 'couteau']);
    expect(Setting::getGiftsWithShoeSize())->toBe(['basket', 'patin']);
    expect(Setting::getGiftsWithSize())->toBe(['veste', 'pantalon']);
});

<?php

use App\Livewire\Admin\TextsManagement;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\CustomText;
use Livewire\Livewire;

beforeEach(function () {
    Setting::clearCache();

    Role::firstOrCreate(['name' => Role::ADMIN]);
    $this->admin = User::factory()->create();
    $this->admin->assignRole(Role::ADMIN);
    $this->actingAs($this->admin);
});

test('texts page is reachable by admins', function () {
    $this->get(route('admin.texts-management'))
        ->assertOk()
        ->assertSee('Textes personnalisables');
});

test('texts page is forbidden for non admins', function () {
    Role::firstOrCreate(['name' => Role::ORGANIZER]);
    $organizer = User::factory()->create();
    $organizer->assignRole(Role::ORGANIZER);

    $this->actingAs($organizer)
        ->get(route('admin.texts-management'))
        ->assertForbidden();
});

test('texts page shows every text with its default as placeholder', function () {
    $component = Livewire::test(TextsManagement::class);

    foreach (CustomText::definitions() as $key => $definition) {
        $component->assertSet("texts.{$key}", '')
            ->assertSee($definition['label']);
    }

    $component->assertSee(CustomText::default(CustomText::MAIL_ACCESS_LINK_INTRO));
});

test('texts page loads stored values, including the moved settings', function () {
    Setting::setValue(Setting::INTRODUCTION_TEXT, 'Bienvenue !');
    Setting::setValue(Setting::REGISTRATION_BLOCKED_REASON, 'Fermé');

    Livewire::test(TextsManagement::class)
        ->assertSet('texts.'.CustomText::HOME_INTRODUCTION, 'Bienvenue !')
        ->assertSet('texts.'.CustomText::REGISTRATION_BLOCKED, 'Fermé');
});

test('admin can save trimmed custom texts', function () {
    Livewire::test(TextsManagement::class)
        ->set('texts.'.CustomText::REGISTRATION_BLOCKED, "  Quota atteint.\nMerci.  ")
        ->set('texts.'.CustomText::MAIL_ACCESS_LINK_SUBJECT, 'Votre lien')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.texts-management'));

    Setting::clearCache();
    expect(CustomText::get(CustomText::REGISTRATION_BLOCKED))->toBe("Quota atteint.\nMerci.")
        ->and(CustomText::get(CustomText::MAIL_ACCESS_LINK_SUBJECT))->toBe('Votre lien');
});

test('clearing a text restores the default one', function () {
    Setting::setValue(CustomText::FORM_SUBMITTED, 'Merci !');

    Livewire::test(TextsManagement::class)
        ->call('resetText', CustomText::FORM_SUBMITTED)
        ->assertSet('texts.'.CustomText::FORM_SUBMITTED, '')
        ->call('save');

    Setting::clearCache();
    expect(CustomText::get(CustomText::FORM_SUBMITTED))
        ->toBe(CustomText::default(CustomText::FORM_SUBMITTED));
});

test('admin can start editing from the default text', function () {
    Livewire::test(TextsManagement::class)
        ->call('useDefault', CustomText::MAIL_FOOTER)
        ->assertSet('texts.'.CustomText::MAIL_FOOTER, CustomText::default(CustomText::MAIL_FOOTER));
});

test('subject length is validated', function () {
    Livewire::test(TextsManagement::class)
        ->set('texts.'.CustomText::MAIL_ACCESS_LINK_SUBJECT, str_repeat('a', 256))
        ->call('save')
        ->assertHasErrors('texts.'.CustomText::MAIL_ACCESS_LINK_SUBJECT);
});

test('saving is refused when a text uses a placeholder not available for it', function () {
    Livewire::test(TextsManagement::class)
        ->set('texts.'.CustomText::MAIL_ACCESS_LINK_SUBJECT, 'Bonjour {famille} de {inconnu}')
        ->call('save')
        ->assertHasErrors('texts.'.CustomText::MAIL_ACCESS_LINK_SUBJECT)
        ->assertSee('{famille}, {inconnu}');

    Setting::clearCache();
    expect(CustomText::stored(CustomText::MAIL_ACCESS_LINK_SUBJECT))->toBe('');
});

test('available placeholders are accepted', function () {
    Livewire::test(TextsManagement::class)
        ->set('texts.'.CustomText::MAIL_ACCESS_LINK_SUBJECT, 'Votre lien {site}')
        ->call('save')
        ->assertHasNoErrors();
});

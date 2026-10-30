<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Livewire::test() skips persistent middleware, so these tests post real requests
 * to the Livewire update endpoint with the snapshot rendered by the page.
 */
function callLivewireAction(object $test, string $html, string $method): \Illuminate\Testing\TestResponse
{
    $snapshot = html_entity_decode((string) Str::of($html)->betweenFirst('wire:snapshot="', '"'), ENT_QUOTES);

    return $test->withHeaders(['X-Livewire' => '1'])
        ->postJson(app('livewire')->getUpdateUri(), [
            'components' => [
                ['snapshot' => $snapshot, 'updates' => [], 'calls' => [['method' => $method, 'params' => [], 'path' => '']]],
            ],
        ]);
}

beforeEach(function () {
    Role::firstOrCreate(['name' => Role::FAMILY_VALIDATOR]);
    $this->user = User::factory()->create();
    $this->user->assignRole(Role::FAMILY_VALIDATOR);
});

test('a user keeping the role can run actions on the page', function () {
    $html = $this->actingAs($this->user)->get(route('admin.family-validation'))->assertOk()->getContent();

    callLivewireAction($this, $html, 'skip')->assertOk();
});

test('a user whose role was removed can no longer run actions on an open page', function () {
    $html = $this->actingAs($this->user)->get(route('admin.family-validation'))->assertOk()->getContent();

    $this->user->removeRole(Role::FAMILY_VALIDATOR);

    callLivewireAction($this, $html, 'skip')->assertForbidden();
});

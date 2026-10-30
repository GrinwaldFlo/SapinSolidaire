<?php

use App\Livewire\Family\Home;
use App\Mail\AccessLinkFloodAlertMail;
use App\Mail\AccessLinkMail;
use App\Models\Role;
use App\Models\Season;
use App\Models\SentMailLog;
use App\Models\User;
use App\Services\AccessLinkRequestGuard;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();

    Season::create([
        'name' => 'Noël 2026',
        'start_date' => now()->subDay(),
        'end_date' => now()->addMonth(),
        'next_family_number' => 1,
    ]);

    config([
        'mail.access_link_limits.per_ip_max' => 3,
        'mail.access_link_limits.per_ip_decay_minutes' => 60,
        'mail.access_link_limits.flood_max' => 5,
        'mail.access_link_limits.flood_decay_minutes' => 10,
        'mail.access_link_limits.flood_block_minutes' => 30,
    ]);

    $this->guard = app(AccessLinkRequestGuard::class);
});

test('the same email cannot request a link twice in a row', function () {
    expect($this->guard->attempt('family@example.com', '10.0.0.1'))->toBeNull();
    expect($this->guard->attempt('Family@Example.com', '10.0.0.2'))->toContain('Veuillez attendre');
});

test('an IP is limited to the configured number of link requests', function () {
    foreach (range(1, 3) as $i) {
        expect($this->guard->attempt("family{$i}@example.com", '10.0.0.1'))->toBeNull();
    }

    expect($this->guard->attempt('family4@example.com', '10.0.0.1'))->toContain('Trop de demandes depuis votre connexion');
    expect($this->guard->attempt('family4@example.com', '10.0.0.2'))->toBeNull();
});

test('a single IP hitting its limit does not trigger the flood block', function () {
    foreach (range(1, 20) as $i) {
        $this->guard->attempt("family{$i}@example.com", '10.0.0.1');
    }

    expect(Cache::has(AccessLinkRequestGuard::FLOOD_BLOCK_KEY))->toBeFalse();
    Mail::assertNothingQueued();
});

test('a swarm on many IPs blocks every link request and alerts the administrators once', function () {
    Role::firstOrCreate(['name' => Role::ADMIN]);
    Role::firstOrCreate(['name' => Role::ORGANIZER]);
    $admin = User::factory()->create(['email' => 'admin@example.com']);
    $admin->assignRole(Role::ADMIN);
    $organizer = User::factory()->create(['email' => 'organizer@example.com']);
    $organizer->assignRole(Role::ORGANIZER);

    foreach (range(1, 5) as $i) {
        expect($this->guard->attempt("bot{$i}@example.com", "10.0.1.{$i}"))->toBeNull();
    }

    expect($this->guard->attempt('bot6@example.com', '10.0.1.6'))->toContain('temporairement suspendu');
    expect($this->guard->attempt('real-family@example.com', '10.0.2.1'))->toContain('temporairement suspendu');
    expect($this->guard->attempt('bot7@example.com', '10.0.1.7'))->toContain('temporairement suspendu');

    Mail::assertQueued(AccessLinkFloodAlertMail::class, 1);
    Mail::assertQueued(AccessLinkFloodAlertMail::class, fn ($mail) => $mail->hasTo('admin@example.com'));
    $this->assertDatabaseHas('sent_mail_logs', [
        'recipient_email' => 'admin@example.com',
        'purpose' => SentMailLog::PURPOSE_ACCESS_LINK_FLOOD_ALERT,
    ]);
    $this->assertDatabaseMissing('sent_mail_logs', ['recipient_email' => 'organizer@example.com']);
});

test('link requests are accepted again once the flood block expires', function () {
    foreach (range(1, 6) as $i) {
        $this->guard->attempt("bot{$i}@example.com", "10.0.1.{$i}");
    }

    $this->travel(31)->minutes();

    expect($this->guard->attempt('real-family@example.com', '10.0.2.1'))->toBeNull();
});

test('home page shows the limit error and sends no link when blocked', function () {
    Cache::put(AccessLinkRequestGuard::FLOOD_BLOCK_KEY, true, now()->addMinutes(30));

    Livewire::test(Home::class)
        ->set('email', 'family@example.com')
        ->call('sendLink')
        ->assertHasErrors('email')
        ->assertSet('emailSent', false);

    Mail::assertNotQueued(AccessLinkMail::class);
});

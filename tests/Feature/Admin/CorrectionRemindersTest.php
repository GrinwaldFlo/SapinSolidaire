<?php

use App\Livewire\Admin\CorrectionReminders;
use App\Mail\CorrectionRequestMail;
use App\Models\Child;
use App\Models\EmailToken;
use App\Models\Family;
use App\Models\FamilySubmissionLog;
use App\Models\GiftRequest;
use App\Models\Role;
use App\Models\Season;
use App\Models\SentMailLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();

    foreach ([Role::ADMIN, Role::VALIDATOR, Role::FAMILY_VALIDATOR, Role::ORGANIZER, Role::RECEPTION] as $roleName) {
        Role::firstOrCreate(['name' => $roleName]);
    }

    $this->admin = User::factory()->create();
    $this->admin->assignRole(Role::ADMIN);

    $this->season = Season::create([
        'name' => 'Noël 2026',
        'start_date' => now()->subDays(10),
        'end_date' => now()->addDays(30),
    ]);

    $this->createRequest = function (string $email, string $status, ?Season $season = null, ?string $comment = null): GiftRequest {
        $family = Family::create([
            'email' => $email,
            'first_name' => 'Jean',
            'last_name' => 'Dupont '.$email,
            'street_name' => 'Rue de la Gare',
            'house_no' => '1',
            'postal_code' => '1000',
            'city' => 'Lausanne',
            'phone' => '0791234567',
        ]);

        return GiftRequest::create([
            'family_id' => $family->id,
            'season_id' => ($season ?? $this->season)->id,
            'status' => $status,
            'rejection_comment' => $comment,
            'status_changed_at' => now(),
        ]);
    };

    $this->makeChild = fn (GiftRequest $request, string $firstName, string $status, ?string $comment = null) => Child::create([
        'gift_request_id' => $request->id,
        'first_name' => $firstName,
        'gender' => Child::GENDER_GIRL,
        'birth_year' => 2018,
        'gift' => 'Livre',
        'status' => $status,
        'rejection_comment' => $comment,
        'status_changed_at' => now(),
    ]);
});

test('lists only refused families and children of the active season', function () {
    ($this->createRequest)('refusee@example.com', GiftRequest::STATUS_REJECTED, comment: 'Justificatif illisible');
    ($this->createRequest)('validee@example.com', GiftRequest::STATUS_VALIDATED);
    ($this->createRequest)('definitif@example.com', GiftRequest::STATUS_REJECTED_FINAL);

    $validated = ($this->createRequest)('enfants@example.com', GiftRequest::STATUS_VALIDATED);
    ($this->makeChild)($validated, 'Emma', Child::STATUS_REJECTED, 'Préciser la taille');
    ($this->makeChild)($validated, 'Hugo', Child::STATUS_VALIDATED);

    $final = ($this->createRequest)('final@example.com', GiftRequest::STATUS_REJECTED_FINAL);
    ($this->makeChild)($final, 'Zoé', Child::STATUS_REJECTED, 'Ancien refus');

    $oldSeason = Season::create([
        'name' => 'Noël 2020',
        'start_date' => now()->subYears(6),
        'end_date' => now()->subYears(6)->addMonth(),
    ]);
    $old = ($this->createRequest)('ancienne@example.com', GiftRequest::STATUS_REJECTED, $oldSeason, 'Ancienne saison');
    ($this->makeChild)($old, 'Léo', Child::STATUS_REJECTED, 'Ancienne saison enfant');

    Livewire::actingAs($this->admin)->test(CorrectionReminders::class)
        ->assertViewHas('requests', fn ($requests) => $requests->pluck('family.email')->all() === ['refusee@example.com'])
        ->assertViewHas('children', fn ($children) => $children->pluck('first_name')->all() === ['Emma'])
        ->assertSee('Justificatif illisible')
        ->assertSee('Préciser la taille');
});

test('shows the date of the last change made by the family', function () {
    $request = ($this->createRequest)('refusee@example.com', GiftRequest::STATUS_REJECTED, comment: 'Justificatif illisible');

    $log = FamilySubmissionLog::create([
        'email' => 'refusee@example.com',
        'action_type' => FamilySubmissionLog::ACTION_UPDATED,
        'family_id' => $request->family_id,
        'gift_request_id' => $request->id,
    ]);
    $log->forceFill(['created_at' => now()->setDate(2026, 11, 2)->setTime(14, 30)])->save();

    Livewire::actingAs($this->admin)->test(CorrectionReminders::class)
        ->assertSee('02/11/2026 14:30');
});

test('toggle all selects every line of a list then clears it', function () {
    $first = ($this->createRequest)('a@example.com', GiftRequest::STATUS_REJECTED, comment: 'Commentaire A');
    $second = ($this->createRequest)('b@example.com', GiftRequest::STATUS_REJECTED, comment: 'Commentaire B');

    $component = Livewire::actingAs($this->admin)->test(CorrectionReminders::class)
        ->call('toggleAll', CorrectionReminders::LIST_REQUESTS);

    expect($component->get('selectedRequestIds'))->toEqualCanonicalizing([$first->id, $second->id]);
    expect($component->get('selectedChildIds'))->toBe([]);

    $component->call('toggleAll', CorrectionReminders::LIST_REQUESTS);

    expect($component->get('selectedRequestIds'))->toBe([]);
});

test('resends the correction request with a new 48h link, one email per family', function () {
    $request = ($this->createRequest)('famille@example.com', GiftRequest::STATUS_REJECTED, comment: 'Justificatif illisible');
    $emma = ($this->makeChild)($request, 'Emma', Child::STATUS_REJECTED, 'Préciser la taille');
    ($this->makeChild)($request, 'Hugo', Child::STATUS_REJECTED, 'Pas sélectionné');

    $oldToken = EmailToken::createForEmail('famille@example.com');

    Livewire::actingAs($this->admin)->test(CorrectionReminders::class)
        ->set('selectedRequestIds', [$request->id])
        ->set('selectedChildIds', [$emma->id])
        ->call('sendReminders')
        ->assertSet('selectedRequestIds', [])
        ->assertSet('selectedChildIds', []);

    $newToken = EmailToken::where('email', 'famille@example.com')->sole();
    expect($newToken->token)->not->toBe($oldToken->token);
    expect($newToken->expires_at->between(now()->addHours(47), now()->addHours(48)->addMinute()))->toBeTrue();

    Mail::assertQueuedCount(1);
    Mail::assertQueued(CorrectionRequestMail::class, fn (CorrectionRequestMail $mail) => $mail->hasTo('famille@example.com')
        && $mail->token === $newToken->token
        && $mail->comment === "Justificatif illisible\n-------------------\nPour l'enfant Emma :\n - Préciser la taille");

    expect(SentMailLog::where('purpose', SentMailLog::PURPOSE_CORRECTION_REMINDER)->count())->toBe(1);

    // Statuses are not changed by a reminder
    expect($request->fresh()->status)->toBe(GiftRequest::STATUS_REJECTED);
    expect($emma->fresh()->status)->toBe(Child::STATUS_REJECTED);
});

test('a refused child alone receives the same message as its correction request', function () {
    $request = ($this->createRequest)('famille@example.com', GiftRequest::STATUS_VALIDATED);
    $emma = ($this->makeChild)($request, 'Emma', Child::STATUS_REJECTED, 'Préciser la taille');

    Livewire::actingAs($this->admin)->test(CorrectionReminders::class)
        ->set('selectedChildIds', [$emma->id])
        ->call('sendReminders');

    Mail::assertQueued(CorrectionRequestMail::class, fn (CorrectionRequestMail $mail) => $mail->comment === "Pour l'enfant Emma :\n - Préciser la taille");
});

test('lines no longer refused or from another season are ignored', function () {
    $request = ($this->createRequest)('famille@example.com', GiftRequest::STATUS_REJECTED, comment: 'Justificatif illisible');
    $validated = ($this->createRequest)('validee@example.com', GiftRequest::STATUS_VALIDATED);
    $validatedChild = ($this->makeChild)($validated, 'Hugo', Child::STATUS_VALIDATED);

    $component = Livewire::actingAs($this->admin)->test(CorrectionReminders::class)
        ->set('selectedRequestIds', [$request->id, $validated->id])
        ->set('selectedChildIds', [$validatedChild->id]);

    // The family corrected its request in the meantime
    $request->update(['status' => GiftRequest::STATUS_PENDING]);

    $component->call('sendReminders');

    Mail::assertNothingQueued();
});

test('no reminder is sent once the modification deadline has passed', function () {
    $this->season->update(['modification_deadline' => now()->subDay()]);
    $request = ($this->createRequest)('famille@example.com', GiftRequest::STATUS_REJECTED, comment: 'Justificatif illisible');

    Livewire::actingAs($this->admin)->test(CorrectionReminders::class)
        ->assertSee('ne peuvent plus corriger')
        ->set('selectedRequestIds', [$request->id])
        ->call('sendReminders');

    Mail::assertNothingQueued();
});

test('no reminder is sent while registrations are blocked by an admin', function () {
    Setting::setValue(Setting::REGISTRATION_BLOCKED, '1');
    $request = ($this->createRequest)('famille@example.com', GiftRequest::STATUS_REJECTED, comment: 'Justificatif illisible');

    Livewire::actingAs($this->admin)->test(CorrectionReminders::class)
        ->assertSee('ne peuvent plus corriger')
        ->set('selectedRequestIds', [$request->id])
        ->call('sendReminders');

    Mail::assertNothingQueued();
});

test('the deadline is read again when sending, not kept from the page opening', function () {
    $request = ($this->createRequest)('famille@example.com', GiftRequest::STATUS_REJECTED, comment: 'Justificatif illisible');

    $component = Livewire::actingAs($this->admin)->test(CorrectionReminders::class)
        ->set('selectedRequestIds', [$request->id]);

    // Another admin moves the deadline into the past while the page is open
    $this->season->update(['modification_deadline' => now()->subDay()]);

    $component->call('sendReminders');

    Mail::assertNothingQueued();
});

test('a child without comment is not reminded and cannot be selected', function () {
    $request = ($this->createRequest)('famille@example.com', GiftRequest::STATUS_VALIDATED);
    $emma = ($this->makeChild)($request, 'Emma', Child::STATUS_REJECTED, 'Préciser la taille');
    $hugo = ($this->makeChild)($request, 'Hugo', Child::STATUS_REJECTED);

    $component = Livewire::actingAs($this->admin)->test(CorrectionReminders::class)
        ->call('toggleAll', CorrectionReminders::LIST_CHILDREN);

    expect($component->get('selectedChildIds'))->toBe([$emma->id]);

    $component->set('selectedChildIds', [$emma->id, $hugo->id])->call('sendReminders');

    Mail::assertQueuedCount(1);
    Mail::assertQueued(CorrectionRequestMail::class, fn (CorrectionRequestMail $mail) => $mail->comment === "Pour l'enfant Emma :\n - Préciser la taille");
});

test('a family with only children without comment receives nothing', function () {
    $request = ($this->createRequest)('famille@example.com', GiftRequest::STATUS_VALIDATED);
    $hugo = ($this->makeChild)($request, 'Hugo', Child::STATUS_REJECTED);

    Livewire::actingAs($this->admin)->test(CorrectionReminders::class)
        ->set('selectedChildIds', [$hugo->id])
        ->call('sendReminders')
        ->assertSee('Aucun rappel envoyé');

    Mail::assertNothingQueued();
});

test('the selection count only includes lines still listed', function () {
    $request = ($this->createRequest)('famille@example.com', GiftRequest::STATUS_REJECTED, comment: 'Justificatif illisible');
    $other = ($this->createRequest)('autre@example.com', GiftRequest::STATUS_REJECTED, comment: 'Adresse incomplète');

    $component = Livewire::actingAs($this->admin)->test(CorrectionReminders::class)
        ->set('selectedRequestIds', [$request->id, $other->id])
        ->assertViewHas('selectedCount', 2);

    // The family corrected its request in the meantime
    $request->update(['status' => GiftRequest::STATUS_PENDING]);

    $component->call('$refresh')->assertViewHas('selectedCount', 1);
});

test('select all is shown checked when every line with a comment is selected', function () {
    ($this->createRequest)('a@example.com', GiftRequest::STATUS_REJECTED, comment: 'Commentaire A');
    ($this->createRequest)('b@example.com', GiftRequest::STATUS_REJECTED);

    Livewire::actingAs($this->admin)->test(CorrectionReminders::class)
        ->call('toggleAll', CorrectionReminders::LIST_REQUESTS)
        ->assertViewHas('allRequestsSelected', true);
});

test('lines corrected in the meantime are reported as such', function () {
    $request = ($this->createRequest)('famille@example.com', GiftRequest::STATUS_REJECTED, comment: 'Justificatif illisible');

    $component = Livewire::actingAs($this->admin)->test(CorrectionReminders::class)
        ->set('selectedRequestIds', [$request->id]);

    $request->update(['status' => GiftRequest::STATUS_PENDING]);

    $component->call('sendReminders')->assertSee('corrigées entre-temps');

    Mail::assertNothingQueued();
});

test('the page is reserved to admins', function (string $roleName) {
    $user = User::factory()->create();
    $user->assignRole($roleName);

    $this->actingAs($user)->get(route('admin.correction-reminders'))->assertForbidden();
})->with([Role::VALIDATOR, Role::FAMILY_VALIDATOR, Role::ORGANIZER, Role::RECEPTION]);

test('admins can open the page', function () {
    $this->actingAs($this->admin)->get(route('admin.correction-reminders'))->assertOk();
});

test('gift requests expose their status label and badge class', function (string $status, string $label, string $badge) {
    $request = new GiftRequest(['status' => $status]);

    expect($request->status_label)->toBe($label);
    expect($request->status_badge_class)->toBe($badge);
})->with([
    [GiftRequest::STATUS_PENDING, 'À valider', 'badge--pending'],
    [GiftRequest::STATUS_VALIDATED, 'Validé', 'badge--validated'],
    [GiftRequest::STATUS_REJECTED, 'Refusé', 'badge--warning'],
    [GiftRequest::STATUS_REJECTED_FINAL, 'Refusé définitivement', 'badge--rejected'],
]);

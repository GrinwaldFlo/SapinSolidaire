<?php

use App\Livewire\Admin\LabelGeneration;
use App\Mail\RequestCompleteMail;
use App\Models\AdminActionLog;
use App\Models\Child;
use App\Models\Family;
use App\Models\GiftRequest;
use App\Models\PickupSlot;
use App\Models\Role;
use App\Models\Season;
use App\Models\SentMailLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Setting::clearCache();
    Storage::fake('local');
    Mail::fake();

    Role::firstOrCreate(['name' => Role::ADMIN]);
    $this->admin = User::factory()->create();
    $this->admin->assignRole(Role::ADMIN);

    $this->season = Season::create([
        'name' => 'Noël 2026',
        'start_date' => now()->subDays(10),
        'end_date' => now()->addDays(30),
        'next_family_number' => 1,
    ]);

    PickupSlot::create([
        'season_id' => $this->season->id,
        'start_datetime' => '2026-12-12 09:00:00',
        'end_datetime' => '2026-12-12 17:00:00',
    ]);

    $this->createRequestWithChildren = function (string $email, array $childStatuses): GiftRequest {
        $family = Family::create([
            'email' => $email,
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'street_name' => 'Rue de la Gare',
            'house_no' => '1',
            'postal_code' => '1000',
            'city' => 'Lausanne',
            'phone' => '0791234567',
        ]);

        $request = GiftRequest::create([
            'family_id' => $family->id,
            'season_id' => $this->season->id,
            'status' => GiftRequest::STATUS_VALIDATED,
        ]);

        foreach ($childStatuses as $index => $status) {
            Child::create([
                'gift_request_id' => $request->id,
                'first_name' => 'Enfant'.$index,
                'gender' => Child::GENDER_BOY,
                'birth_year' => 2018,
                'gift' => 'Vélo',
                'status' => $status,
                'validated_at' => now(),
            ]);
        }

        return $request;
    };
});

test('generating labels sends one request complete mail per family with all printed children', function () {
    $requestA = ($this->createRequestWithChildren)('a@example.com', [Child::STATUS_VALIDATED, Child::STATUS_VALIDATED]);
    ($this->createRequestWithChildren)('b@example.com', [Child::STATUS_VALIDATED]);
    ($this->createRequestWithChildren)('c@example.com', [Child::STATUS_PENDING]);

    $this->actingAs($this->admin);

    Livewire::test(LabelGeneration::class)
        ->assertSet('requestCompleteMailCount', 2)
        ->call('generatePdf')
        ->assertSee('2 e-mail(s) envoyé(s) aux familles');

    Mail::assertQueued(RequestCompleteMail::class, 2);
    Mail::assertQueued(RequestCompleteMail::class, fn ($mail) => $mail->hasTo('a@example.com')
        && $mail->giftRequest->is($requestA)
        && $mail->children->count() === 2);
    Mail::assertNotQueued(RequestCompleteMail::class, fn ($mail) => $mail->hasTo('c@example.com'));

    expect(SentMailLog::where('purpose', SentMailLog::PURPOSE_REQUEST_COMPLETE)->count())->toBe(2)
        ->and(AdminActionLog::where('action_type', AdminActionLog::ACTION_EMAIL_SENT)->count())->toBe(2)
        ->and(AdminActionLog::where('gift_request_id', $requestA->id)->first()->user_id)->toBe($this->admin->id);
});

test('request complete mail contains the expected sentence with pickup date', function () {
    $request = ($this->createRequestWithChildren)('a@example.com', [Child::STATUS_VALIDATED]);
    $request->load('family', 'children');

    $html = (new RequestCompleteMail($request, $this->season, $request->children))->render();

    expect($html)->toContain('Votre demande est à présent complète et ne peut plus être modifiée.')
        ->toContain("venir chercher les cadeaux souhaités le samedi 12 décembre 2026 à l'heure qui vous sera indiquée.")
        ->toContain('Enfant0');
});

test('label generation page warns that emails will be sent', function () {
    ($this->createRequestWithChildren)('a@example.com', [Child::STATUS_VALIDATED]);

    $this->actingAs($this->admin);

    Livewire::test(LabelGeneration::class)
        ->assertSee("Envoi automatique d'e-mails", false)
        ->assertSee('1 famille(s)');
});

test('request complete mail is not sent again after resetting and regenerating labels', function () {
    $request = ($this->createRequestWithChildren)('a@example.com', [Child::STATUS_VALIDATED]);

    $this->actingAs($this->admin);

    $component = Livewire::test(LabelGeneration::class)->call('generatePdf');

    expect($request->refresh()->request_complete_email_sent_at)->not->toBeNull();

    $component->call('resetPrintedLabels')
        ->assertSet('requestCompleteMailCount', 0)
        ->call('generatePdf')
        ->assertSee('0 e-mail(s) envoyé(s) aux familles');

    Mail::assertQueued(RequestCompleteMail::class, 1);
    expect(SentMailLog::where('purpose', SentMailLog::PURPOSE_REQUEST_COMPLETE)->count())->toBe(1);
});

test('request complete mail waits until every child of the request is printed', function () {
    $request = ($this->createRequestWithChildren)('a@example.com', [Child::STATUS_VALIDATED, Child::STATUS_PENDING]);
    $pendingChild = $request->children()->where('status', Child::STATUS_PENDING)->first();

    $this->actingAs($this->admin);

    Livewire::test(LabelGeneration::class)
        ->assertSet('requestCompleteMailCount', 0)
        ->call('generatePdf')
        ->assertSee('0 e-mail(s) envoyé(s) aux familles');

    Mail::assertNothingQueued();
    expect($request->refresh()->request_complete_email_sent_at)->toBeNull();

    $pendingChild->setStatus(Child::STATUS_VALIDATED);

    Livewire::test(LabelGeneration::class)
        ->assertSet('requestCompleteMailCount', 1)
        ->call('generatePdf');

    Mail::assertQueued(RequestCompleteMail::class, fn ($mail) => $mail->hasTo('a@example.com')
        && $mail->children->count() === 2);
    expect($request->refresh()->request_complete_email_sent_at)->not->toBeNull();
});

test('finally rejected children do not block the request complete mail', function () {
    ($this->createRequestWithChildren)('a@example.com', [Child::STATUS_VALIDATED, Child::STATUS_REJECTED_FINAL]);

    $this->actingAs($this->admin);

    Livewire::test(LabelGeneration::class)
        ->assertSet('requestCompleteMailCount', 1)
        ->call('generatePdf');

    Mail::assertQueued(RequestCompleteMail::class, fn ($mail) => $mail->children->count() === 1);
});

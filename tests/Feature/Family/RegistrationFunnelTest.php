<?php

use App\Livewire\Admin\RegistrationFunnelStats;
use App\Livewire\Family\GiftRequestForm;
use App\Livewire\Family\Home;
use App\Models\EmailToken;
use App\Models\Family;
use App\Models\GiftRequest;
use App\Models\RegistrationFunnelCounter as Counter;
use App\Models\Role;
use App\Models\Season;
use App\Models\Setting;
use App\Models\User;
use App\Services\AddressValidationService;
use App\Services\RegistrationFunnel;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(function () {
    Setting::clearCache();
    Mail::fake();

    $this->season = Season::create([
        'name' => 'Noël 2026',
        'start_date' => now()->subDay(),
        'end_date' => now()->addMonth(),
        'modification_deadline' => now()->addMonth(),
    ]);

    $this->mock(AddressValidationService::class)
        ->shouldReceive('validate')
        ->andReturn(['Valide' => true, 'Message' => '', 'FormatedAddress' => []]);
});

function funnelCount(Season $season, string $step): int
{
    return (int) Counter::where('season_id', $season->id)->where('step', $step)->value('count');
}

function setFunnelCounts(Season $season, array $counts): void
{
    foreach ($counts as $step => $count) {
        Counter::create(['season_id' => $season->id, 'step' => $step, 'count' => $count]);
    }
}

function acceptEligibility($component)
{
    return $component
        ->call('acceptConsecutiveYears')
        ->call('acceptPickupCommitment')
        ->call('acceptCity');
}

function fillFamilyAndChild($component)
{
    return $component
        ->set('firstName', 'Paul')
        ->set('lastName', 'Martin')
        ->set('streetName', 'Rue de Test')
        ->set('houseNo', '1')
        ->set('postalCode', '1000')
        ->set('city', 'Lausanne')
        ->set('phone', '+41791234567')
        ->set('children.0.first_name', 'Emma')
        ->set('children.0.gender', 'girl')
        ->set('children.0.birth_year', (string) ((int) date('Y') - 5))
        ->set('children.0.gift', 'Livre');
}

function createRegisteredFamily(Season $season, string $email): Family
{
    $family = Family::create([
        'email' => $email,
        'first_name' => 'Jean',
        'last_name' => 'Dupont',
        'street_name' => 'Rue du Lac',
        'house_no' => '1',
        'postal_code' => '1400',
        'city' => 'Yverdon-les-Bains',
        'phone' => '0791234567',
    ]);

    GiftRequest::create([
        'family_id' => $family->id,
        'season_id' => $season->id,
        'status' => GiftRequest::STATUS_PENDING,
    ]);

    return $family;
}

test('each step of a complete registration is counted once', function () {
    Livewire::test(Home::class)->set('email', 'new@example.com')->call('sendLink');
    $token = EmailToken::where('email', 'new@example.com')->first();

    $component = acceptEligibility(Livewire::test(GiftRequestForm::class, ['token' => $token->token]));
    fillFamilyAndChild($component)->call('submit')->assertSet('submitted', true);

    foreach ([
        Counter::STEP_LINK_REQUESTED,
        Counter::STEP_LINK_OPENED,
        Counter::STEP_CONSECUTIVE_YEARS_ACCEPTED,
        Counter::STEP_PICKUP_ACCEPTED,
        Counter::STEP_CITY_ACCEPTED,
        Counter::STEP_SUBMIT_ATTEMPTED,
        Counter::STEP_SUBMITTED,
    ] as $step) {
        expect(funnelCount($this->season, $step))->toBe(1, $step);
    }
});

test('a family that stops midway only counts the steps it reached', function () {
    $token = EmailToken::createForEmail('stop@example.com');

    Livewire::test(GiftRequestForm::class, ['token' => $token->token])
        ->call('acceptConsecutiveYears');

    expect(funnelCount($this->season, Counter::STEP_CONSECUTIVE_YEARS_ACCEPTED))->toBe(1)
        ->and(funnelCount($this->season, Counter::STEP_PICKUP_ACCEPTED))->toBe(0);
});

test('reloading the form or requesting a new link does not count a family twice', function () {
    Livewire::test(Home::class)->set('email', 'twice@example.com')->call('sendLink');
    $token = EmailToken::where('email', 'twice@example.com')->first();

    Livewire::test(GiftRequestForm::class, ['token' => $token->token]);
    Livewire::test(GiftRequestForm::class, ['token' => $token->token]);

    $this->travel(2)->minutes();
    $newToken = EmailToken::createForEmail('twice@example.com');
    Livewire::test(GiftRequestForm::class, ['token' => $newToken->token]);

    expect($newToken->token)->not->toBe($token->token)
        ->and(funnelCount($this->season, Counter::STEP_LINK_OPENED))->toBe(1);
});

test('a submit failing validation counts as attempted but not as submitted', function () {
    $token = EmailToken::createForEmail('invalid@example.com');

    acceptEligibility(Livewire::test(GiftRequestForm::class, ['token' => $token->token]))
        ->call('submit')
        ->assertSet('submitted', false);

    expect(funnelCount($this->season, Counter::STEP_SUBMIT_ATTEMPTED))->toBe(1)
        ->and(funnelCount($this->season, Counter::STEP_SUBMITTED))->toBe(0);
});

test('multi step form counts its own steps', function () {
    Setting::setValue(Setting::FAMILY_FORM_MULTI_STEP_ENABLED, '1');
    $token = EmailToken::createForEmail('wizard@example.com');

    $component = acceptEligibility(Livewire::test(GiftRequestForm::class, ['token' => $token->token]));

    $component->call('nextFamilyFormStep');
    expect(funnelCount($this->season, Counter::STEP_FAMILY_INFO_COMPLETED))->toBe(0);

    fillFamilyAndChild($component);
    $component->set('cityConfirmed', true)->call('nextFamilyFormStep');
    expect(funnelCount($this->season, Counter::STEP_FAMILY_INFO_COMPLETED))->toBe(1);

    $component->call('setAnonymousChoice', false);
    expect(funnelCount($this->season, Counter::STEP_ANONYMOUS_CHOICE_MADE))->toBe(1)
        ->and(funnelCount($this->season, Counter::STEP_CHILDREN_COMPLETED))->toBe(0);

    $component->call('nextFamilyFormStep');
    expect(funnelCount($this->season, Counter::STEP_CHILDREN_COMPLETED))->toBe(1);
});

test('a family with a request this season is not counted', function () {
    createRegisteredFamily($this->season, 'registered@example.com');

    Livewire::test(Home::class)->set('email', 'registered@example.com')->call('sendLink');
    $token = EmailToken::where('email', 'registered@example.com')->first();

    fillFamilyAndChild(Livewire::test(GiftRequestForm::class, ['token' => $token->token]))->call('submit');

    expect(Counter::where('season_id', $this->season->id)->count())->toBe(0);
});

test('reopening the link after submitting does not count again', function () {
    $token = EmailToken::createForEmail('done@example.com');

    fillFamilyAndChild(acceptEligibility(Livewire::test(GiftRequestForm::class, ['token' => $token->token])))
        ->call('submit');

    fillFamilyAndChild(Livewire::test(GiftRequestForm::class, ['token' => $token->token]))
        ->call('submit')
        ->assertSet('submitted', true);

    expect(funnelCount($this->season, Counter::STEP_SUBMITTED))->toBe(1)
        ->and(funnelCount($this->season, Counter::STEP_LINK_OPENED))->toBe(1);
});

test('families blocked when opening their link are counted with the reason', function () {
    Setting::setValue(Setting::REGISTRATION_BLOCKED, '1');
    $token = EmailToken::createForEmail('closed@example.com');

    Livewire::test(GiftRequestForm::class, ['token' => $token->token])->assertSet('canModify', false);

    expect(funnelCount($this->season, Counter::BLOCKED_REGISTRATION_CLOSED))->toBe(1)
        ->and(funnelCount($this->season, Counter::STEP_LINK_OPENED))->toBe(1);
});

test('an invalid token is not counted', function () {
    Livewire::test(GiftRequestForm::class, ['token' => 'unknown'])->assertSet('tokenValid', false);

    expect(Counter::count())->toBe(0);
});

test('a link requested with no active season is not counted', function () {
    $this->season->update(['start_date' => now()->addYear(), 'end_date' => now()->addYears(2)]);

    Livewire::test(Home::class)->set('email', 'early@example.com')->call('sendLink');

    expect(Counter::count())->toBe(0);
});

test('counters hold no data about the families', function () {
    expect(Schema::getColumnListing('registration_funnel_counters'))
        ->toEqualCanonicalizing(['id', 'season_id', 'step', 'count']);
});

test('a recording failure does not break the family flow', function () {
    Schema::drop('registration_funnel_counters');
    $token = EmailToken::createForEmail('resilient@example.com');

    Livewire::test(GiftRequestForm::class, ['token' => $token->token])
        ->assertSet('tokenValid', true)
        ->call('acceptConsecutiveYears')
        ->assertSet('consecutiveYearsAccepted', true);
});

test('report computes the stops between steps', function () {
    setFunnelCounts($this->season, [
        Counter::STEP_LINK_REQUESTED => 100,
        Counter::STEP_LINK_OPENED => 80,
        Counter::STEP_CONSECUTIVE_YEARS_ACCEPTED => 60,
        Counter::STEP_PICKUP_ACCEPTED => 60,
        Counter::STEP_CITY_ACCEPTED => 50,
        Counter::STEP_SUBMIT_ATTEMPTED => 30,
        Counter::STEP_SUBMITTED => 25,
        Counter::BLOCKED_CONSECUTIVE_YEARS => 5,
    ]);

    $report = app(RegistrationFunnel::class)->report($this->season);
    $stops = collect($report['steps'])->pluck('stopped', 'label');

    expect($report['started'])->toBe(100)
        ->and($report['submitted'])->toBe(25)
        ->and($report['completionPercent'])->toBe(25)
        ->and($stops['Lien demandé'])->toBe(20)
        // 80 opened - 60 next - 5 blocked by a rule
        ->and($stops['Lien ouvert'])->toBe(15)
        ->and($stops['Commune confirmée (accès au formulaire)'])->toBe(20)
        ->and($stops['Demande enregistrée'])->toBeNull()
        ->and($report['steps'][0]['percent'])->toBe(100)
        ->and($report['steps'][1]['percent'])->toBe(80);
});

test('report hides form mode steps never reached and shows them otherwise', function () {
    $labels = fn () => collect(app(RegistrationFunnel::class)->report($this->season)['steps'])->pluck('label');

    expect($labels())->not->toContain('Informations de la famille complétées');

    setFunnelCounts($this->season, [Counter::STEP_FAMILY_INFO_COMPLETED => 1]);

    expect($labels())->toContain('Informations de la famille complétées');
});

test('report never shows a negative stop and handles an empty season', function () {
    expect(app(RegistrationFunnel::class)->report($this->season))
        ->toMatchArray(['started' => 0, 'completionPercent' => 0, 'biggestStop' => null]);

    setFunnelCounts($this->season, [
        Counter::STEP_LINK_REQUESTED => 1,
        Counter::STEP_LINK_OPENED => 3,
    ]);

    $report = app(RegistrationFunnel::class)->report($this->season);

    expect($report['steps'][0]['stopped'])->toBe(0)
        ->and($report['started'])->toBe(3);
});

test('statistics page is limited to organizers and admins and scoped to the season', function () {
    $other = Season::create([
        'name' => 'Noël 2025',
        'start_date' => now()->subYears(2),
        'end_date' => now()->subYears(2)->addMonth(),
    ]);
    setFunnelCounts($this->season, [Counter::STEP_LINK_REQUESTED => 42]);
    setFunnelCounts($other, [Counter::STEP_LINK_REQUESTED => 7]);

    Role::firstOrCreate(['name' => Role::ORGANIZER]);
    Role::firstOrCreate(['name' => Role::RECEPTION]);

    $reception = User::factory()->create();
    $reception->assignRole(Role::RECEPTION);
    $this->actingAs($reception)->get(route('admin.registration-funnel'))->assertForbidden();

    $organizer = User::factory()->create();
    $organizer->assignRole(Role::ORGANIZER);
    $this->actingAs($organizer)->get(route('admin.registration-funnel'))->assertOk();

    Livewire::actingAs($organizer)->test(RegistrationFunnelStats::class)
        ->assertSet('selectedSeasonId', $this->season->id)
        ->assertViewHas('report', fn (array $report) => $report['started'] === 42)
        ->set('selectedSeasonId', $other->id)
        ->assertSee('Lien demandé')
        ->assertViewHas('report', fn (array $report) => $report['started'] === 7);
});

test('different families increment the same counter', function () {
    foreach (['first@example.com', 'second@example.com'] as $email) {
        Livewire::test(GiftRequestForm::class, ['token' => EmailToken::createForEmail($email)->token]);
    }

    expect(funnelCount($this->season, Counter::STEP_LINK_OPENED))->toBe(2)
        ->and(Counter::where('season_id', $this->season->id)->where('step', Counter::STEP_LINK_OPENED)->count())->toBe(1);
});

test('a family returning with an expired token not cleaned yet counts as a new registration', function () {
    $token = EmailToken::createForEmail('returning@example.com');
    Livewire::test(GiftRequestForm::class, ['token' => $token->token]);

    $this->travel(49)->hours();
    $newToken = EmailToken::createForEmail('returning@example.com');
    Livewire::test(GiftRequestForm::class, ['token' => $newToken->token]);

    expect(funnelCount($this->season, Counter::STEP_LINK_OPENED))->toBe(2);
});

test('the season status of the home page cannot be changed by the browser', function () {
    Livewire::test(Home::class)->set('seasonStatus', 'none');
})->throws(CannotUpdateLockedPropertyException::class);

test('counters survive the deletion of the expired token holding the steps', function () {
    $token = EmailToken::createForEmail('expired@example.com');
    Livewire::test(GiftRequestForm::class, ['token' => $token->token]);

    $this->travel(49)->hours();
    EmailToken::cleanExpired();

    expect(EmailToken::where('email', 'expired@example.com')->exists())->toBeFalse()
        ->and(funnelCount($this->season, Counter::STEP_LINK_OPENED))->toBe(1);
});

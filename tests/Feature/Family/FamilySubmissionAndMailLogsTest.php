<?php

use App\Livewire\Family\GiftRequestForm;
use App\Livewire\Family\Home;
use App\Models\EmailToken;
use App\Models\FamilySubmissionLog;
use App\Models\Season;
use App\Models\SentMailLog;
use App\Services\AddressValidationService;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    $this->season = Season::create([
        'name' => 'Noël 2026',
        'start_date' => now()->subDay(),
        'end_date' => now()->addMonth(),
        'modification_deadline' => now()->addMonth(),
        'next_family_number' => 1,
    ]);

    $this->mock(AddressValidationService::class)
        ->shouldReceive('validate')
        ->andReturn([
            'Valide' => true,
            'Message' => '',
            'FormatedAddress' => [],
        ]);
});

test('public access link request is logged in sent mail logs', function () {
    Mail::fake();

    Livewire::test(Home::class)
        ->set('email', 'new-family@example.com')
        ->call('sendLink')
        ->assertSet('emailSent', true);

    $this->assertDatabaseHas('sent_mail_logs', [
        'recipient_email' => 'new-family@example.com',
        'purpose' => SentMailLog::PURPOSE_ACCESS_LINK,
        'sent_by_label' => 'Famille',
    ]);
});

test('family form submission logs created then updated actions', function () {
    $token = EmailToken::createForEmail('family-submit@example.com');

    Livewire::test(GiftRequestForm::class, ['token' => $token->token])
        ->call('acceptConsecutiveYears')
        ->call('acceptPickupCommitment')
        ->call('acceptCity')
        ->set('firstName', 'Paul')
        ->set('lastName', 'Martin')
        ->set('streetName', 'Rue de Test')
        ->set('houseNo', '1')
        ->set('postalCode', '1000')
        ->set('city', 'Lausanne')
        ->set('phone', '+41791234567')
        ->set('children.0.first_name', 'Emma')
        ->set('children.0.gender', 'girl')
        ->set('children.0.birth_year', '2018')
        ->set('children.0.gift', 'Livre')
        ->call('submit');

    $this->assertDatabaseHas('family_submission_logs', [
        'email' => 'family-submit@example.com',
        'action_type' => FamilySubmissionLog::ACTION_CREATED,
    ]);

    Livewire::test(GiftRequestForm::class, ['token' => $token->token])
        ->set('children.0.gift', 'Jeu éducatif')
        ->call('submit');

    $this->assertDatabaseHas('family_submission_logs', [
        'email' => 'family-submit@example.com',
        'action_type' => FamilySubmissionLog::ACTION_UPDATED,
    ]);
});

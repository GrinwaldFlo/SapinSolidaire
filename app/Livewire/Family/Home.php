<?php

namespace App\Livewire\Family;

use App\Mail\AccessLinkMail;
use App\Models\EmailToken;
use App\Models\Season;
use App\Models\SentMailLog;
use App\Services\AccessLinkRequestGuard;
use App\Services\CustomText;
use App\Services\SeasonService;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.family')]
class Home extends Component
{
    public string $email = '';

    public bool $emailSent = false;

    public string $statusMessage = '';

    public string $seasonStatus = '';

    public ?Season $season = null;

    public ?string $registrationClosedMessage = null;

    public function mount(SeasonService $seasonService): void
    {
        $status = $seasonService->getCurrentStatus();
        $this->seasonStatus = $status['status'];
        $this->statusMessage = $status['message'];
        $this->season = $status['season'];

        if ($this->seasonStatus === 'active') {
            $this->registrationClosedMessage = $seasonService->getRegistrationClosedMessage($this->season);
        }
    }

    public function sendLink(AccessLinkRequestGuard $guard): void
    {
        $this->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Veuillez entrer votre adresse e-mail.',
            'email.email' => 'Veuillez entrer une adresse e-mail valide.',
        ]);

        // Rate limiting (per email, per IP, global flood)
        $error = $guard->attempt($this->email, (string) request()->ip());

        if ($error !== null) {
            $this->addError('email', $error);

            return;
        }

        // Create token and send email
        $token = EmailToken::createForEmail($this->email);

        $mail = new AccessLinkMail($this->email, $token->token);
        Mail::to($this->email)->queue($mail);
        SentMailLog::logQueuedMail(
            recipientEmail: $this->email,
            purpose: SentMailLog::PURPOSE_ACCESS_LINK,
            mailable: $mail,
            senderLabel: 'Famille'
        );

        $this->emailSent = true;
    }

    public function render()
    {
        return view('livewire.family.home', [
            'introductionText' => CustomText::get(CustomText::HOME_INTRODUCTION),
            'pickupSlotsInfo' => CustomText::get(CustomText::HOME_PICKUP_SLOTS_INFO),
            'pickupSlots' => $this->season
                ? $this->season->pickupSlots()->orderBy('start_datetime')->get()
                : collect(),
        ]);
    }
}

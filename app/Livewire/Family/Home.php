<?php

namespace App\Livewire\Family;

use App\Mail\AccessLinkMail;
use App\Models\EmailToken;
use App\Models\Season;
use App\Models\Setting;
use App\Services\SeasonService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
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

    public function mount(SeasonService $seasonService): void
    {
        $status = $seasonService->getCurrentStatus();
        $this->seasonStatus = $status['status'];
        $this->statusMessage = $status['message'];
        $this->season = $status['season'];
    }

    public function sendLink(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Veuillez entrer votre adresse e-mail.',
            'email.email' => 'Veuillez entrer une adresse e-mail valide.',
        ]);

        // Rate limiting
        $rateLimitSeconds = (int) config('mail.rate_limit_seconds', 5);
        $key = 'email-request:'.$this->email;

        if (RateLimiter::tooManyAttempts($key, 1)) {
            $seconds = RateLimiter::availableIn($key);
            $this->addError('email', "Veuillez attendre {$seconds} secondes avant de réessayer.");

            return;
        }

        RateLimiter::hit($key, $rateLimitSeconds);

        // Create token and send email
        $token = EmailToken::createForEmail($this->email);

        Mail::to($this->email)->queue(new AccessLinkMail($this->email, $token->token));

        $this->emailSent = true;
    }

    public function render()
    {
        return view('livewire.family.home', [
            'introductionText' => Setting::getIntroductionText(),
        ]);
    }
}

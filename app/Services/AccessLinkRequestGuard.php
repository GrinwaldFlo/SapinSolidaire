<?php

namespace App\Services;

use App\Mail\AccessLinkFloodAlertMail;
use App\Models\Role;
use App\Models\SentMailLog;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Rate limits the public access link requests: per email, per IP, and globally.
 * A global flood (bot swarm on many IPs) blocks every request for a while and alerts the administrators.
 */
class AccessLinkRequestGuard
{
    public const FLOOD_BLOCK_KEY = 'access-link:flood-block';

    private const FLOOD_COUNTER_KEY = 'access-link:flood-counter';

    /**
     * Count the request when it is allowed.
     *
     * @return string|null Error message for the family, null when the link can be sent
     */
    public function attempt(string $email, string $ip): ?string
    {
        if (Cache::has(self::FLOOD_BLOCK_KEY)) {
            return $this->floodBlockedMessage();
        }

        $emailKey = 'email-request:'.mb_strtolower($email);
        if (RateLimiter::tooManyAttempts($emailKey, 1)) {
            $seconds = RateLimiter::availableIn($emailKey);

            return "Veuillez attendre {$seconds} secondes avant de réessayer.";
        }

        $ipKey = 'email-request-ip:'.$ip;
        if (RateLimiter::tooManyAttempts($ipKey, $this->limit('per_ip_max'))) {
            $minutes = (int) ceil(RateLimiter::availableIn($ipKey) / 60);

            return "Trop de demandes depuis votre connexion. Veuillez réessayer dans {$minutes} minute(s).";
        }

        RateLimiter::hit($emailKey, (int) config('mail.rate_limit_seconds', 5));
        RateLimiter::hit($ipKey, $this->limit('per_ip_decay_minutes') * 60);

        // Only allowed requests are counted, so a single IP cannot trigger the global block
        $total = RateLimiter::hit(self::FLOOD_COUNTER_KEY, $this->limit('flood_decay_minutes') * 60);
        if ($total > $this->limit('flood_max')) {
            $this->startFloodBlock($total, $ip);

            return $this->floodBlockedMessage();
        }

        return null;
    }

    protected function startFloodBlock(int $total, string $ip): void
    {
        $blockedUntil = now()->addMinutes($this->limit('flood_block_minutes'));

        // Cache::add is atomic: the administrators are alerted once per block
        if (! Cache::add(self::FLOOD_BLOCK_KEY, $blockedUntil->toIso8601String(), $blockedUntil)) {
            return;
        }

        RateLimiter::clear(self::FLOOD_COUNTER_KEY);

        Log::warning('Access link requests blocked: flood detected', [
            'requests' => $total,
            'window_minutes' => $this->limit('flood_decay_minutes'),
            'blocked_until' => $blockedUntil->toIso8601String(),
            'last_ip' => $ip,
        ]);

        $this->alertAdministrators($total, $blockedUntil);
    }

    protected function alertAdministrators(int $total, \DateTimeInterface $blockedUntil): void
    {
        $emails = User::whereHas('roles', fn ($query) => $query->where('name', Role::ADMIN))->pluck('email');

        foreach ($emails as $email) {
            $mail = new AccessLinkFloodAlertMail($total, $this->limit('flood_decay_minutes'), $blockedUntil);
            Mail::to($email)->queue($mail);
            SentMailLog::logQueuedMail(
                recipientEmail: $email,
                purpose: SentMailLog::PURPOSE_ACCESS_LINK_FLOOD_ALERT,
                mailable: $mail,
                senderLabel: 'Système'
            );
        }
    }

    protected function floodBlockedMessage(): string
    {
        return 'L\'envoi de liens est temporairement suspendu en raison d\'un nombre inhabituel de demandes. Veuillez réessayer plus tard.';
    }

    protected function limit(string $key): int
    {
        return (int) config("mail.access_link_limits.{$key}");
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EmailToken extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'token',
        'expires_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Check if the token is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at < now();
    }

    /**
     * Check if the token is valid.
     */
    public function isValid(): bool
    {
        return ! $this->isExpired();
    }

    /**
     * Create a new token for the given email.
     */
    public static function createForEmail(string $email): self
    {
        // Delete any existing tokens for this email
        self::where('email', $email)->delete();

        return self::create([
            'email' => $email,
            'token' => Str::random(64),
            'expires_at' => now()->addHours(48),
        ]);
    }

    /**
     * Find a valid token.
     */
    public static function findValidToken(string $token): ?self
    {
        return self::where('token', $token)
            ->where('expires_at', '>', now())
            ->first();
    }

    /**
     * Clean up expired tokens.
     */
    public static function cleanExpired(): int
    {
        return self::where('expires_at', '<', now())->delete();
    }
}

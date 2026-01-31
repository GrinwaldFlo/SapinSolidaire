<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    public const SITE_NAME = 'site_name';
    public const ALLOWED_POSTAL_CODES = 'allowed_postal_codes';
    public const MAX_CONSECUTIVE_YEARS = 'max_consecutive_years';
    public const GIFT_SUGGESTIONS = 'gift_suggestions';
    public const INTRODUCTION_TEXT = 'introduction_text';
    public const REPLY_TO_EMAIL = 'reply_to_email';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Get a setting value by key.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever("setting_{$key}", function () use ($key, $default) {
            $setting = self::where('key', $key)->first();

            return $setting?->value ?? $default;
        });
    }

    /**
     * Set a setting value by key.
     */
    public static function setValue(string $key, mixed $value): void
    {
        self::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        Cache::forget("setting_{$key}");
    }

    /**
     * Get site name.
     */
    public static function getSiteName(): string
    {
        return self::getValue(self::SITE_NAME, 'Sapin Solidaire');
    }

    /**
     * Get allowed postal codes as array.
     */
    public static function getAllowedPostalCodes(): array
    {
        $value = self::getValue(self::ALLOWED_POSTAL_CODES, '');

        if (empty($value)) {
            return [];
        }

        return array_map('trim', explode(',', $value));
    }

    /**
     * Check if a postal code is allowed.
     */
    public static function isPostalCodeAllowed(string $postalCode): bool
    {
        $allowed = self::getAllowedPostalCodes();

        if (empty($allowed)) {
            return true; // If no restrictions, allow all
        }

        return in_array($postalCode, $allowed);
    }

    /**
     * Get max consecutive years.
     */
    public static function getMaxConsecutiveYears(): int
    {
        return (int) self::getValue(self::MAX_CONSECUTIVE_YEARS, 3);
    }

    /**
     * Get gift suggestions as array.
     */
    public static function getGiftSuggestions(): array
    {
        $value = self::getValue(self::GIFT_SUGGESTIONS, '');

        if (empty($value)) {
            return [];
        }

        return array_filter(array_map('trim', explode("\n", $value)));
    }

    /**
     * Get introduction text.
     */
    public static function getIntroductionText(): string
    {
        return self::getValue(self::INTRODUCTION_TEXT, '');
    }

    /**
     * Get reply-to email address.
     */
    public static function getReplyToEmail(): ?string
    {
        return self::getValue(self::REPLY_TO_EMAIL);
    }

    /**
     * Clear all settings cache.
     */
    public static function clearCache(): void
    {
        $keys = [
            self::SITE_NAME,
            self::ALLOWED_POSTAL_CODES,
            self::MAX_CONSECUTIVE_YEARS,
            self::GIFT_SUGGESTIONS,
            self::INTRODUCTION_TEXT,
            self::REPLY_TO_EMAIL,
        ];

        foreach ($keys as $key) {
            Cache::forget("setting_{$key}");
        }
    }
}

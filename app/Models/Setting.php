<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory, HasUuids;

    public const SITE_NAME = 'site_name';
    public const LOGO_PATH = 'logo_path';
    public const ALLOWED_CITIES = 'allowed_cities';
    public const MAX_CONSECUTIVE_YEARS = 'max_consecutive_years';
    public const GIFT_SUGGESTIONS = 'gift_suggestions';
    public const GIFT_RESTRICTIONS = 'gift_restrictions';
    public const GIFTS_WITH_SHOE_SIZE = 'gifts_with_shoe_size';
    public const GIFTS_WITH_SIZE = 'gifts_with_size';
    public const INTRODUCTION_TEXT = 'introduction_text';
    public const REPLY_TO_EMAIL = 'reply_to_email';
    public const CODE_PREFIX = 'code_prefix';
    public const CODE_FAMILY_PADDING = 'code_family_padding';
    public const PROOF_OF_HABITATION_ENABLED = 'proof_of_habitation_enabled';
    public const FAMILY_FORM_MULTI_STEP_ENABLED = 'family_form_multi_step_enabled';
    public const PDF_STYLE = 'pdf_style';
    public const MAX_CHILD_AGE = 'max_child_age';
    public const VALIDATION_COMMENT_TEMPLATES = 'validation_comment_templates';

    public const PDF_STYLE_LABEL = 'label';
    public const PDF_STYLE_GRID = 'grid';

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
     * Get logo path.
     */
    public static function getLogoPath(): string
    {
        return self::getValue(self::LOGO_PATH, '/logo.svg');
    }

    /**
     * Get allowed cities as array.
     */
    public static function getAllowedCities(): array
    {
        $value = self::getValue(self::ALLOWED_CITIES, '');

        if (empty($value)) {
            return [];
        }

        return array_values(
            array_filter(
                array_map('trim', explode(',', $value)),
                static fn (string $city): bool => $city !== ''
            )
        );
    }

    /**
     * Check if a city is allowed.
     */
    public static function isCityAllowed(string $city): bool
    {
        $allowed = self::getAllowedCities();

        if (empty($allowed)) {
            return true; // If no restrictions, allow all
        }

        return in_array($city, $allowed);
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
     * Get gift restrictions (forbidden gifts) as array.
     */
    public static function getGiftRestrictions(): array
    {
        $value = self::getValue(self::GIFT_RESTRICTIONS, '');

        if (empty($value)) {
            return [];
        }

        return array_filter(array_map('trim', explode("\n", $value)));
    }

    /**
     * Get gifts requiring shoe size as array.
     */
    public static function getGiftsWithShoeSize(): array
    {
        $value = self::getValue(self::GIFTS_WITH_SHOE_SIZE, '');

        if (empty($value)) {
            return [];
        }

        return array_filter(array_map('trim', explode("\n", $value)));
    }

    /**
     * Get gifts requiring clothing size as array.
     */
    public static function getGiftsWithSize(): array
    {
        $value = self::getValue(self::GIFTS_WITH_SIZE, '');

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
     * Get code prefix for children codes.
     */
    public static function getCodePrefix(): string
    {
        return self::getValue(self::CODE_PREFIX, '');
    }

    /**
     * Get code family number padding.
     */
    public static function getCodeFamilyPadding(): int
    {
        return (int) self::getValue(self::CODE_FAMILY_PADDING, 4);
    }

    /**
     * Check if proof of habitation is enabled.
     */
    public static function isProofOfHabitationEnabled(): bool
    {
        return (bool) self::getValue(self::PROOF_OF_HABITATION_ENABLED, false);
    }

    /**
     * Get PDF style (label or grid).
     */
    public static function getPdfStyle(): string
    {
        return self::getValue(self::PDF_STYLE, self::PDF_STYLE_LABEL);
    }

    /**
     * Check if family multi-step form mode is enabled.
     */
    public static function isFamilyFormMultiStepEnabled(): bool
    {
        return (bool) self::getValue(self::FAMILY_FORM_MULTI_STEP_ENABLED, false);
    }

    /**
     * Get max child age (inclusive, evaluated on 31.12 of current year).
     */
    public static function getMaxChildAge(): int
    {
        return (int) self::getValue(self::MAX_CHILD_AGE, 12);
    }

    /**
     * Get predefined validation comment templates.
     *
     * @return array<int, string>
     */
    public static function getValidationCommentTemplates(): array
    {
        $value = self::getValue(self::VALIDATION_COMMENT_TEMPLATES, '[]');

        if (! is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        if (! is_array($decoded)) {
            return [];
        }

        return array_values(
            array_filter(
                array_map(static fn ($message): string => trim((string) $message), $decoded),
                static fn (string $message): bool => $message !== ''
            )
        );
    }

    /**
     * Persist predefined validation comment templates.
     *
     * @param array<int, string> $templates
     */
    public static function setValidationCommentTemplates(array $templates): void
    {
        $cleanedTemplates = array_values(
            array_filter(
                array_map(static fn (string $message): string => trim($message), $templates),
                static fn (string $message): bool => $message !== ''
            )
        );

        self::setValue(
            self::VALIDATION_COMMENT_TEMPLATES,
            json_encode($cleanedTemplates, JSON_UNESCAPED_UNICODE) ?: '[]'
        );
    }

    /**
     * Clear all settings cache.
     */
    public static function clearCache(): void
    {
        $keys = [
            self::SITE_NAME,
            self::ALLOWED_CITIES,
            self::MAX_CONSECUTIVE_YEARS,
            self::GIFT_SUGGESTIONS,
            self::GIFT_RESTRICTIONS,
            self::GIFTS_WITH_SHOE_SIZE,
            self::GIFTS_WITH_SIZE,
            self::INTRODUCTION_TEXT,
            self::REPLY_TO_EMAIL,
            self::CODE_PREFIX,
            self::CODE_FAMILY_PADDING,
            self::PROOF_OF_HABITATION_ENABLED,
            self::FAMILY_FORM_MULTI_STEP_ENABLED,
            self::PDF_STYLE,
            self::MAX_CHILD_AGE,
            self::VALIDATION_COMMENT_TEMPLATES,
        ];

        foreach ($keys as $key) {
            Cache::forget("setting_{$key}");
        }
    }
}

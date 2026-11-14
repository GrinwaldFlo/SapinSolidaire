<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Formats dates shown to families (pages and emails) in French,
 * whatever the current application locale (browser language, queue worker).
 */
class FrenchDate
{
    public const LOCALE = 'fr';

    /**
     * Format a date with translated day/month names, e.g. "mercredi 23 décembre 2026".
     */
    public static function format(?CarbonInterface $date, string $format = 'l d F Y'): ?string
    {
        return $date?->copy()->locale(self::LOCALE)->translatedFormat($format);
    }
}

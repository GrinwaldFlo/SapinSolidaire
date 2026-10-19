<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Matches a gift text against a list of configured keywords.
 *
 * Matching ignores accents and case, and a keyword may appear anywhere in the
 * gift text, including as part of a word (« chauss » matches « Chaussures »).
 */
class GiftKeywordMatcher
{
    /**
     * @param  array<int, string>  $keywords
     */
    public static function matches(?string $gift, array $keywords): bool
    {
        if ($gift === null || $gift === '' || $keywords === []) {
            return false;
        }

        $normalizedGift = self::normalize($gift);

        foreach ($keywords as $keyword) {
            $normalizedKeyword = self::normalize($keyword);

            if ($normalizedKeyword !== '' && str_contains($normalizedGift, $normalizedKeyword)) {
                return true;
            }
        }

        return false;
    }

    public static function normalize(string $value): string
    {
        return trim(mb_strtolower(Str::ascii($value)));
    }
}

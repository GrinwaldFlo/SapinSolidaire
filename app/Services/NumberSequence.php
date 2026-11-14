<?php

namespace App\Services;

/**
 * Continuous numbering (family numbers, child numbers): the smallest free number fills the gaps.
 */
class NumberSequence
{
    /**
     * Smallest positive number not in $used (1 when empty, a gap when there is one, else max + 1).
     *
     * @param  iterable<int|string|null>  $used
     */
    public static function firstFree(iterable $used): int
    {
        $taken = [];

        foreach ($used as $number) {
            if ($number !== null) {
                $taken[(int) $number] = true;
            }
        }

        $candidate = 1;

        while (isset($taken[$candidate])) {
            $candidate++;
        }

        return $candidate;
    }
}

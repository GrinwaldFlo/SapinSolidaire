<?php

namespace App\Services;

use App\Models\Child;
use App\Models\Family;
use App\Models\GiftRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FamilyDuplicateService
{
    /**
     * Find all duplicate pairs sorted by descending score.
     * Returns a collection of arrays: ['familyA' => Family, 'familyB' => Family, 'score' => int, 'details' => array]
     */
    public function findDuplicates(float $threshold = 40.0): Collection
    {
        // Raise time limit for this expensive operation — it only runs on explicit admin request
        // and results are cached server-side by the Livewire component.
        set_time_limit(300);

        $families = Family::with(['giftRequests.children', 'giftRequests.season'])->get();
        $list     = $families->values();
        $count    = $list->count();
        $pairs    = collect();

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $a      = $list[$i];
                $b      = $list[$j];
                $result = $this->score($a, $b);

                if ($result['score'] >= $threshold) {
                    $pairs->push([
                        'familyA' => $a,
                        'familyB' => $b,
                        'score'   => $result['score'],
                        'details' => $result['details'],
                    ]);
                }
            }
        }

        return $pairs->sortByDesc('score')->values();
    }

    /**
     * Compute similarity score (0-100) between two families.
     */
    public function score(Family $a, Family $b): array
    {
        $details = [];
        $total = 0.0;
        $max = 0.0;

        // Last name (30 pts)
        $max += 30;
        $s = $this->similarityRatio(
            mb_strtolower(trim($a->last_name ?? '')),
            mb_strtolower(trim($b->last_name ?? ''))
        );
        $pts = round($s * 30, 1);
        $details['last_name'] = $pts;
        $total += $pts;

        // First name (15 pts)
        $max += 15;
        $s = $this->similarityRatio(
            mb_strtolower(trim($a->first_name ?? '')),
            mb_strtolower(trim($b->first_name ?? ''))
        );
        $pts = round($s * 15, 1);
        $details['first_name'] = $pts;
        $total += $pts;

        // Street name (10 pts)
        $max += 10;
        $s = $this->similarityRatio(
            mb_strtolower(trim($a->street_name ?? '')),
            mb_strtolower(trim($b->street_name ?? ''))
        );
        $pts = round($s * 10, 1);
        $details['street_name'] = $pts;
        $total += $pts;

        // Postal code (10 pts – exact)
        $max += 10;
        $pts = ($a->postal_code && $a->postal_code === $b->postal_code) ? 10.0 : 0.0;
        $details['postal_code'] = $pts;
        $total += $pts;

        // City (5 pts)
        $max += 5;
        $s = $this->similarityRatio(
            mb_strtolower(trim($a->city ?? '')),
            mb_strtolower(trim($b->city ?? ''))
        );
        $pts = round($s * 5, 1);
        $details['city'] = $pts;
        $total += $pts;

        // Phone (10 pts – normalised exact)
        $max += 10;
        $phoneA = $this->normalizePhone($a->phone ?? '');
        $phoneB = $this->normalizePhone($b->phone ?? '');
        $pts = ($phoneA && $phoneB && $phoneA === $phoneB) ? 10.0 : 0.0;
        $details['phone'] = $pts;
        $total += $pts;

        // Children similarity (20 pts)
        $max += 20;
        $pts = round($this->childrenScore($a, $b) * 20, 1);
        $details['children'] = $pts;
        $total += $pts;

        $score = $max > 0 ? round(($total / $max) * 100, 1) : 0;

        return ['score' => $score, 'details' => $details];
    }

    /**
     * Score children overlap between 0 and 1.
     */
    private function childrenScore(Family $a, Family $b): float
    {
        $childrenA = $a->giftRequests->flatMap(fn ($r) => $r->children)->values();
        $childrenB = $b->giftRequests->flatMap(fn ($r) => $r->children)->values();

        if ($childrenA->isEmpty() && $childrenB->isEmpty()) {
            return 1.0; // both have no children – neutral
        }

        if ($childrenA->isEmpty() || $childrenB->isEmpty()) {
            return 0.0;
        }

        $matched = 0;
        $usedB = [];

        foreach ($childrenA as $ca) {
            $bestMatch = -1;
            $bestScore = 0.0;

            foreach ($childrenB as $idx => $cb) {
                if (in_array($idx, $usedB)) {
                    continue;
                }

                $s = $this->childSimilarity($ca, $cb);
                if ($s > $bestScore) {
                    $bestScore = $s;
                    $bestMatch = $idx;
                }
            }

            if ($bestScore >= 0.6) {
                $matched++;
                $usedB[] = $bestMatch;
            }
        }

        $total = max($childrenA->count(), $childrenB->count());

        return $total > 0 ? $matched / $total : 0.0;
    }

    private function childSimilarity(Child $a, Child $b): float
    {
        $score = 0.0;

        // First name (60%)
        $score += 0.6 * $this->similarityRatio(
            mb_strtolower(trim($a->first_name ?? '')),
            mb_strtolower(trim($b->first_name ?? ''))
        );

        // Birth year (30%)
        if ($a->birth_year && $b->birth_year) {
            $diff = abs($a->birth_year - $b->birth_year);
            $score += 0.3 * ($diff === 0 ? 1.0 : ($diff === 1 ? 0.5 : 0.0));
        }

        // Gender (10%)
        if ($a->gender && $b->gender && $a->gender === $b->gender) {
            $score += 0.1;
        }

        return $score;
    }

    /**
     * Similarity ratio between two strings (0–1) using similar_text.
     */
    private function similarityRatio(string $a, string $b): float
    {
        if ($a === '' && $b === '') {
            return 1.0;
        }
        if ($a === '' || $b === '') {
            return 0.0;
        }
        similar_text($a, $b, $percent);

        return $percent / 100.0;
    }

    /**
     * Normalize a phone number to 10 local digits.
     */
    private function normalizePhone(string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', $phone);
        if (str_starts_with($digits, '0041')) {
            $digits = '0'.substr($digits, 4);
        } elseif (str_starts_with($digits, '41') && strlen($digits) === 11) {
            $digits = '0'.substr($digits, 2);
        }

        return (strlen($digits) === 10 && str_starts_with($digits, '0')) ? $digits : null;
    }

    /**
     * Merge familyB into familyA.
     *
     * @param  array<string, string>  $chosenFields  Fields to take from familyB (keys: email, first_name, …)
     */
    public function merge(Family $keepFamily, Family $removeFamily, array $chosenFields = []): void
    {
        DB::transaction(function () use ($keepFamily, $removeFamily, $chosenFields) {
            // Apply chosen fields on the kept family
            $familyFields = ['email', 'first_name', 'last_name', 'phone', 'street_name', 'house_no', 'postal_code', 'city'];
            foreach ($familyFields as $field) {
                if (in_array($field, $chosenFields)) {
                    $keepFamily->$field = $removeFamily->$field;
                }
            }
            $keepFamily->save();

            // Re-assign gift requests from the removed family
            foreach ($removeFamily->giftRequests()->with('children')->get() as $removeRequest) {
                $existingRequest = $keepFamily->giftRequests()
                    ->where('season_id', $removeRequest->season_id)
                    ->first();

                if ($existingRequest) {
                    // Season conflict: move children to the existing request, then delete the duplicate request
                    $removeRequest->children()->update(['gift_request_id' => $existingRequest->id]);
                    $removeRequest->delete();
                } else {
                    // Simply re-assign the request
                    $removeRequest->family_id = $keepFamily->id;
                    $removeRequest->save();
                }
            }

            $removeFamily->delete();
        });
    }
}

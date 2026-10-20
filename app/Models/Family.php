<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Family extends Model
{
    use HasFactory, HasUuids;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'first_name',
        'last_name',
        'street_name',
        'house_no',
        'postal_code',
        'city',
        'phone',
        'excluded_at',
        'exclusion_reason',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'excluded_at' => 'datetime',
    ];

    /**
     * Check if the family is permanently excluded (blocked for all future seasons).
     */
    public function isExcluded(): bool
    {
        return $this->excluded_at !== null;
    }

    /**
     * Permanently exclude the family from all future seasons.
     */
    public function exclude(?string $reason = null): void
    {
        $this->excluded_at = now();
        $this->exclusion_reason = $reason;
        $this->save();
    }

    /**
     * Lift the family exclusion.
     */
    public function clearExclusion(): void
    {
        $this->excluded_at = null;
        $this->exclusion_reason = null;
        $this->save();
    }

    /**
     * Get gift requests for this family.
     */
    public function giftRequests(): HasMany
    {
        return $this->hasMany(GiftRequest::class);
    }

    /**
     * Get gift request for a specific season.
     */
    public function getRequestForSeason(Season $season): ?GiftRequest
    {
        return $this->giftRequests()->where('season_id', $season->id)->first();
    }

    /**
     * Get the number of consecutive years, right before the given season, in which
     * the family made a gift request that was not rejected.
     */
    public function getConsecutiveYearsCountBefore(Season $season): int
    {
        $requestYears = $this->giftRequests()
            ->join('seasons', 'gift_requests.season_id', '=', 'seasons.id')
            ->where('gift_requests.season_id', '!=', $season->id)
            ->whereNotIn('gift_requests.status', [
                GiftRequest::STATUS_REJECTED,
                GiftRequest::STATUS_REJECTED_FINAL,
            ])
            ->pluck('seasons.start_date')
            ->map(fn ($startDate): int => (int) Carbon::parse($startDate)->format('Y'))
            ->unique();

        $count = 0;
        $previousYear = (int) $season->start_date->format('Y') - 1;

        while ($requestYears->contains($previousYear - $count)) {
            $count++;
        }

        return $count;
    }

    /**
     * Check if a new request for the given season would exceed the consecutive years limit.
     * The limit includes the season itself: with a limit of 3, requests in the 2 previous
     * years block the third one.
     */
    public function hasReachedConsecutiveYearsLimit(Season $season, int $maxConsecutiveYears): bool
    {
        return $this->getConsecutiveYearsCountBefore($season) >= $maxConsecutiveYears - 1;
    }

    /**
     * Get the full name of the family contact.
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Get the full address.
     */
    public function getFullAddressAttribute(): string
    {
        $streetLine = trim(implode(' ', array_filter([
            $this->street_name,
            $this->house_no,
        ])));

        return trim("{$streetLine}, {$this->postal_code} {$this->city}", ' ,');
    }

    /**
     * Normalize the stored phone number to a 10-digit local Swiss number (0XXXXXXXXX).
     */
    protected function normalizePhone(): ?string
    {
        if (! $this->phone) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $this->phone);

        // +41... or 0041...
        if (str_starts_with($digits, '0041')) {
            $digits = '0'.substr($digits, 4);
        } elseif (str_starts_with($digits, '41') && strlen($digits) === 11) {
            $digits = '0'.substr($digits, 2);
        }

        if (strlen($digits) !== 10 || ! str_starts_with($digits, '0')) {
            return null;
        }

        return $digits;
    }

    /**
     * Get the phone number formatted for Swiss display: 076 444 56 66
     */
    public function getFormattedPhoneAttribute(): ?string
    {
        $digits = $this->normalizePhone();

        if (! $digits) {
            return $this->phone;
        }

        return substr($digits, 0, 3).' '.substr($digits, 3, 3).' '.substr($digits, 6, 2).' '.substr($digits, 8, 2);
    }

    /**
     * Get the phone number in E.164 format for tel: links: +41XXXXXXXXX
     */
    public function getTelPhoneAttribute(): ?string
    {
        $digits = $this->normalizePhone();

        if (! $digits) {
            return $this->phone;
        }

        return '+41'.substr($digits, 1);
    }
}

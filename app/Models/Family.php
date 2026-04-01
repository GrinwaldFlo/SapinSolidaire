<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    ];

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
     * Get the number of consecutive years the family has requested gifts.
     */
    public function getConsecutiveYearsCount(): int
    {
        $requests = $this->giftRequests()
            ->join('seasons', 'gift_requests.season_id', '=', 'seasons.id')
            ->orderByDesc('seasons.start_date')
            ->get(['seasons.start_date']);

        if ($requests->isEmpty()) {
            return 0;
        }

        $count = 0;
        $currentYear = (int) now()->format('Y');

        foreach ($requests as $request) {
            $requestYear = (int) $request->start_date->format('Y');

            if ($requestYear === $currentYear - $count) {
                $count++;
            } else {
                break;
            }
        }

        return $count;
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

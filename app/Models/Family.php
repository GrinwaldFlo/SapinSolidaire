<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Family extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'first_name',
        'last_name',
        'address',
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
        return trim("{$this->address}, {$this->postal_code} {$this->city}");
    }
}

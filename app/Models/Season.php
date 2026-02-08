<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Season extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'modification_deadline',
        'pickup_start_date',
        'pickup_address',
        'family_limit_per_slot',
        'slot_duration_minutes',
        'responsible_name',
        'responsible_phone',
        'responsible_email',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'modification_deadline' => 'date',
        'pickup_start_date' => 'date',
        'family_limit_per_slot' => 'integer',
        'slot_duration_minutes' => 'integer',
    ];

    /**
     * Get gift requests for this season.
     */
    public function giftRequests(): HasMany
    {
        return $this->hasMany(GiftRequest::class);
    }

    /**
     * Get pickup slots for this season.
     */
    public function pickupSlots(): HasMany
    {
        return $this->hasMany(PickupSlot::class);
    }

    /**
     * Check if the season is currently active.
     */
    public function isActive(): bool
    {
        $today = now()->toDateString();

        return $this->start_date <= $today && $this->end_date >= $today;
    }

    /**
     * Check if the season is in the future.
     */
    public function isFuture(): bool
    {
        return $this->start_date > now()->toDateString();
    }

    /**
     * Check if modifications are still allowed.
     */
    public function canModify(): bool
    {
        if (! $this->modification_deadline) {
            return true;
        }

        return now()->toDateString() <= $this->modification_deadline;
    }

    /**
     * Get the currently active season.
     */
    public static function getActive(): ?self
    {
        $today = now()->toDateString();

        return self::where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->first();
    }

    /**
     * Get the next upcoming season.
     */
    public static function getNextFuture(): ?self
    {
        return self::where('start_date', '>', now()->toDateString())
            ->orderBy('start_date')
            ->first();
    }
}

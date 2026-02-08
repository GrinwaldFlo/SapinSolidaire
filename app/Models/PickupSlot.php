<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PickupSlot extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'season_id',
        'start_datetime',
        'end_datetime',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
    ];

    /**
     * Get the season for this pickup slot.
     */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    /**
     * Get the gift requests assigned to this slot.
     */
    public function giftRequests(): HasMany
    {
        return $this->hasMany(GiftRequest::class);
    }

    /**
     * Get the number of families assigned to this slot.
     */
    public function getAssignedCountAttribute(): int
    {
        return $this->giftRequests()->count();
    }

    /**
     * Check if this slot is full.
     */
    public function isFull(): bool
    {
        $limit = $this->season->family_limit_per_slot;

        if (! $limit) {
            return false;
        }

        return $this->assigned_count >= $limit;
    }

    /**
     * Get the remaining capacity of this slot.
     */
    public function getRemainingCapacityAttribute(): int
    {
        $limit = $this->season->family_limit_per_slot;

        if (! $limit) {
            return PHP_INT_MAX;
        }

        return max(0, $limit - $this->assigned_count);
    }
}

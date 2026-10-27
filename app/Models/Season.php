<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\DB;

class Season extends Model
{
    use HasFactory, HasUuids;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'modification_deadline',
        'max_gifts',
        'pickup_address',
        'family_limit_per_slot',
        'slot_duration_minutes',
        'responsible_name',
        'responsible_phone',
        'responsible_email',
        'next_family_number',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'modification_deadline' => 'date',
        'max_gifts' => 'integer',
        'family_limit_per_slot' => 'integer',
        'slot_duration_minutes' => 'integer',
        'next_family_number' => 'integer',
    ];

    /**
     * Get gift requests for this season.
     */
    public function giftRequests(): HasMany
    {
        return $this->hasMany(GiftRequest::class);
    }

    /**
     * Get children (gifts) requested for this season.
     */
    public function children(): HasManyThrough
    {
        return $this->hasManyThrough(Child::class, GiftRequest::class);
    }

    /**
     * Number of gifts counted against the season limit: every child except rejected ones.
     */
    public function countedGiftsCount(): int
    {
        return $this->children()
            ->whereNotIn('children.status', [Child::STATUS_REJECTED, Child::STATUS_REJECTED_FINAL])
            ->count();
    }

    /**
     * Check if the maximum number of gifts for the season is reached (never when no limit is set).
     */
    public function hasReachedMaxGifts(): bool
    {
        return $this->max_gifts !== null && $this->countedGiftsCount() >= $this->max_gifts;
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
        if (!$this->modification_deadline) {
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
     * Human readable pickup date(s), e.g. "le samedi 12 décembre 2026"
     * or "entre le samedi 12 décembre 2026 et le dimanche 13 décembre 2026".
     */
    public function pickupDateText(): string
    {
        $slots = $this->pickupSlots()->orderBy('start_datetime')->get();

        $firstStart = $slots->first()?->start_datetime;
        $lastEnd = $slots->sortBy('end_datetime')->last()?->end_datetime;

        if (! $firstStart || ! $lastEnd) {
            return '';
        }

        if ($firstStart->toDateString() === $lastEnd->toDateString()) {
            return 'le '.$firstStart->translatedFormat('l d F Y');
        }

        return sprintf(
            'entre le %s et le %s',
            $firstStart->translatedFormat('l d F Y'),
            $lastEnd->translatedFormat('l d F Y')
        );
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

    /**
     * Atomically assign and return the next family number for this season.
     */
    public function assignNextFamilyNumber(): int
    {
        return DB::transaction(function () {
            $updated = self::where('id', $this->id)
                ->lockForUpdate()
                ->first();

            $number = $updated->next_family_number;

            $updated->increment('next_family_number');

            return $number;
        });
    }
}

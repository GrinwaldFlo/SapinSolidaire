<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GiftRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_REJECTED_FINAL = 'rejected_final';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'family_id',
        'season_id',
        'status',
        'status_changed_at',
        'rejection_comment',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'status_changed_at' => 'datetime',
    ];

    /**
     * Get the family for this request.
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * Get the season for this request.
     */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    /**
     * Get children for this request.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Child::class);
    }

    /**
     * Check if the request can be modified.
     */
    public function canModify(): bool
    {
        if (! $this->season->canModify()) {
            return false;
        }

        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_REJECTED,
            self::STATUS_VALIDATED,
        ]);
    }

    /**
     * Set status and update status_changed_at timestamp.
     */
    public function setStatus(string $status, ?string $comment = null): void
    {
        $this->status = $status;
        $this->status_changed_at = now();

        if ($comment !== null) {
            $this->rejection_comment = $comment;
        }

        $this->save();
    }

    /**
     * Reset status to pending.
     */
    public function resetToPending(): void
    {
        $this->setStatus(self::STATUS_PENDING);
        $this->rejection_comment = null;
        $this->save();
    }
}

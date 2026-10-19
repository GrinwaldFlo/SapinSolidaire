<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GiftRequest extends Model
{
    use HasFactory, HasUuids;

    public const STATUS_PENDING = 'pending';

    public const STATUS_VALIDATED = 'validated';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_REJECTED_FINAL = 'rejected_final';

    /**
     * Child statuses meaning the gift card has been printed.
     *
     * @var array<int, string>
     */
    public const PRINTED_CHILD_STATUSES = [
        Child::STATUS_PRINTED,
        Child::STATUS_RECEIVED,
        Child::STATUS_GIVEN,
    ];

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'family_id',
        'season_id',
        'family_number',
        'status',
        'status_changed_at',
        'rejection_comment',
        'proof_of_habitation_path',
        'pickup_slot_id',
        'slot_start_datetime',
        'slot_end_datetime',
        'request_complete_email_sent_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'family_number' => 'integer',
        'status_changed_at' => 'datetime',
        'slot_start_datetime' => 'datetime',
        'slot_end_datetime' => 'datetime',
        'request_complete_email_sent_at' => 'datetime',
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
     * Get the pickup slot for this request.
     */
    public function pickupSlot(): BelongsTo
    {
        return $this->belongsTo(PickupSlot::class);
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

        if ($status === self::STATUS_REJECTED_FINAL) {
            $this->family?->exclude($comment ?? $this->rejection_comment);
        }
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

    /**
     * Whether every child still in the request (final rejections excluded) has its card printed.
     * Statuses in $alsoReady are treated as printed, e.g. validated children about to be printed.
     *
     * @param  array<int, string>  $alsoReady
     */
    public function hasAllChildrenPrinted(array $alsoReady = []): bool
    {
        $readyStatuses = array_merge(self::PRINTED_CHILD_STATUSES, $alsoReady);
        $children = $this->children->where('status', '!=', Child::STATUS_REJECTED_FINAL);

        return $children->isNotEmpty()
            && $children->every(fn (Child $child) => in_array($child->status, $readyStatuses, true));
    }

    /**
     * Whether the "request complete" mail is due: never sent and all children printed.
     *
     * @param  array<int, string>  $alsoReady
     */
    public function isRequestCompleteMailDue(array $alsoReady = []): bool
    {
        return $this->request_complete_email_sent_at === null
            && filled($this->family?->email)
            && $this->hasAllChildrenPrinted($alsoReady);
    }

    /**
     * Atomically stamp the "request complete" mail as sent. Returns false if it was already stamped.
     */
    public function markRequestCompleteMailSent(): bool
    {
        $now = now();
        $updated = static::whereKey($this->getKey())
            ->whereNull('request_complete_email_sent_at')
            ->update(['request_complete_email_sent_at' => $now]);

        if ($updated === 1) {
            $this->request_complete_email_sent_at = $now;
            $this->syncOriginalAttribute('request_complete_email_sent_at');
        }

        return $updated === 1;
    }
}

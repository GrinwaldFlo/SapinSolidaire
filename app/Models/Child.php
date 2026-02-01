<?php

namespace App\Models;

use App\Services\CodeGeneratorService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Child extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_REJECTED_FINAL = 'rejected_final';
    public const STATUS_PRINTED = 'printed';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_GIVEN = 'given';

    public const GENDER_BOY = 'boy';
    public const GENDER_GIRL = 'girl';
    public const GENDER_UNSPECIFIED = 'unspecified';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'gift_request_id',
        'first_name',
        'gender',
        'anonymous',
        'birth_year',
        'height',
        'gift',
        'shoe_size',
        'code',
        'status',
        'status_changed_at',
        'rejection_comment',
        'validated_at',
        'confirmation_email_sent_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'birth_year' => 'integer',
        'height' => 'integer',
        'anonymous' => 'boolean',
        'status_changed_at' => 'datetime',
        'validated_at' => 'datetime',
        'confirmation_email_sent_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Child $child) {
            if (empty($child->code)) {
                $child->code = app(CodeGeneratorService::class)->generate();
            }
        });
    }

    /**
     * Get the gift request for this child.
     */
    public function giftRequest(): BelongsTo
    {
        return $this->belongsTo(GiftRequest::class);
    }

    /**
     * Get the family through the gift request.
     */
    public function getFamilyAttribute(): ?Family
    {
        return $this->giftRequest?->family;
    }

    /**
     * Get the season through the gift request.
     */
    public function getSeasonAttribute(): ?Season
    {
        return $this->giftRequest?->season;
    }

    /**
     * Calculate the child's age.
     */
    public function getAgeAttribute(): int
    {
        return (int) now()->format('Y') - $this->birth_year;
    }

    /**
     * Check if the child can be modified.
     */
    public function canModify(): bool
    {
        if (! $this->giftRequest->season->canModify()) {
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

        if ($status === self::STATUS_VALIDATED && $this->validated_at === null) {
            $this->validated_at = now();
        }

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

    /**
     * Get status label in French.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'À valider',
            self::STATUS_VALIDATED => 'Validé',
            self::STATUS_REJECTED => 'Refusé',
            self::STATUS_REJECTED_FINAL => 'Refusé définitivement',
            self::STATUS_PRINTED => 'Imprimé',
            self::STATUS_RECEIVED => 'Reçu',
            self::STATUS_GIVEN => 'Donné',
            default => $this->status,
        };
    }

    /**
     * Get gender label in French.
     */
    public function getGenderLabelAttribute(): string
    {
        return match ($this->gender) {
            self::GENDER_BOY => 'Garçon',
            self::GENDER_GIRL => 'Fille',
            self::GENDER_UNSPECIFIED => 'Non précisé',
            default => 'Non précisé',
        };
    }
}

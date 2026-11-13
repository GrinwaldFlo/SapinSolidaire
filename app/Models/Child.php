<?php

namespace App\Models;

use App\Models\Concerns\HasStatusBadge;
use App\Services\NumberSequence;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Child extends Model
{
    use HasFactory, HasStatusBadge, HasUuids;

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
        'child_number',
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
        'child_number' => 'integer',
        'status_changed_at' => 'datetime',
        'validated_at' => 'datetime',
        'confirmation_email_sent_at' => 'datetime',
    ];

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
     * Get a human-readable age label ("bébé" when 0, "X ans" otherwise).
     */
    public function getFormattedAgeAttribute(): string
    {
        return $this->age === 0 ? 'bébé' : $this->age.' ans';
    }

    /**
     * Height and shoe size summary (e.g. "120 cm / P. 32"), null when neither is set.
     */
    public function getSizesSummaryAttribute(): ?string
    {
        $parts = array_filter([
            $this->height ? $this->height.' cm' : null,
            $this->shoe_size ? 'P. '.$this->shoe_size : null,
        ]);

        return $parts === [] ? null : implode(' / ', $parts);
    }

    /**
     * Validated children of validated requests of the season: their gift card can be printed.
     * A family sent back to validation keeps its validated children, but they wait for it.
     */
    public function scopeReadyToPrint(Builder $query, Season $season): Builder
    {
        return $query->where('status', self::STATUS_VALIDATED)
            ->whereHas('giftRequest', fn (Builder $q) => $q
                ->where('season_id', $season->id)
                ->where('status', GiftRequest::STATUS_VALIDATED));
    }

    /**
     * Whether the gift card is printed: the child's data and decision are then frozen.
     */
    public function hasPrintedCard(): bool
    {
        return in_array($this->status, GiftRequest::PRINTED_CHILD_STATUSES, true);
    }

    /**
     * Children whose gift card is not printed yet, i.e. whose status can still be decided.
     */
    public function scopeWithoutPrintedCard(Builder $query): Builder
    {
        return $query->whereNotIn('status', GiftRequest::PRINTED_CHILD_STATUSES);
    }

    /**
     * Child of the active season in the given status, or null (ids sent by the browser are not trusted).
     */
    public static function findInSeasonWithStatus(string $childId, Season $season, string $status): ?self
    {
        return self::whereKey($childId)
            ->where('status', $status)
            ->whereHas('giftRequest', fn (Builder $q) => $q->where('season_id', $season->id))
            ->first();
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
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            ...self::decisionStatusLabels(),
            self::STATUS_PRINTED => 'Imprimé',
            self::STATUS_RECEIVED => 'Reçu',
            self::STATUS_GIVEN => 'Donné',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statusBadgeClasses(): array
    {
        return [
            ...self::decisionStatusBadgeClasses(),
            self::STATUS_PRINTED => 'badge--printed',
            self::STATUS_RECEIVED => 'badge--received',
            self::STATUS_GIVEN => 'badge--given',
        ];
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

    /**
     * Give the child its number and code at print time, with the family number when the family has none yet.
     * The child number is the smallest free number of the family (gaps filled).
     * Once assigned, the numbers never change (a reprinted child keeps its code).
     */
    public function assignChildNumberAndCode(): void
    {
        if ($this->child_number !== null && $this->code !== null) {
            return;
        }

        DB::transaction(function () {
            $giftRequest = $this->giftRequest;
            $familyNumber = $giftRequest->assignFamilyNumber();

            if ($this->child_number === null) {
                GiftRequest::whereKey($giftRequest->id)->lockForUpdate()->first();

                $this->child_number = NumberSequence::firstFree(
                    self::where('gift_request_id', $giftRequest->id)->whereNotNull('child_number')->pluck('child_number')
                );
            }

            $this->code = self::generateCode(
                Setting::getCodePrefix(),
                $familyNumber,
                $this->child_number,
                Setting::getCodeFamilyPadding()
            );
            $this->save();
        });
    }

    /**
     * Generate a formatted code: prefix + padded family number + / + child number.
     */
    public static function generateCode(string $prefix, int $familyNumber, int $childNumber, int $padding = 4): string
    {
        return $prefix.str_pad((string) $familyNumber, $padding, '0', STR_PAD_LEFT).'/'.$childNumber;
    }

    /**
     * Regenerate codes for all children that already have a code assigned.
     */
    public static function regenerateAllCodes(string $prefix, int $padding): void
    {
        Child::whereNotNull('code')
            ->whereNotNull('child_number')
            ->with('giftRequest')
            ->chunkById(100, function ($children) use ($prefix, $padding) {
                foreach ($children as $child) {
                    if ($child->giftRequest && $child->giftRequest->family_number !== null) {
                        $child->code = self::generateCode(
                            $prefix,
                            $child->giftRequest->family_number,
                            $child->child_number,
                            $padding
                        );
                        $child->save();
                    }
                }
            });
    }
}

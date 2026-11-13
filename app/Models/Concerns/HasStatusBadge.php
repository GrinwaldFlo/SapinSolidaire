<?php

namespace App\Models\Concerns;

/**
 * French status label and badge class of a model with decision statuses
 * (pending, validated, rejected, rejected_final), shared by GiftRequest and Child.
 * A model with more statuses overrides statusLabels() / statusBadgeClasses() and adds them.
 */
trait HasStatusBadge
{
    /**
     * Labels of the decision statuses shared by every model using this trait.
     *
     * @return array<string, string>
     */
    protected static function decisionStatusLabels(): array
    {
        return [
            self::STATUS_PENDING => 'À valider',
            self::STATUS_VALIDATED => 'Validé',
            self::STATUS_REJECTED => 'Refusé',
            self::STATUS_REJECTED_FINAL => 'Refusé définitivement',
        ];
    }

    /**
     * Badge classes of the decision statuses (rejected = correction expected, final = refused).
     *
     * @return array<string, string>
     */
    protected static function decisionStatusBadgeClasses(): array
    {
        return [
            self::STATUS_PENDING => 'badge--pending',
            self::STATUS_VALIDATED => 'badge--validated',
            self::STATUS_REJECTED => 'badge--warning',
            self::STATUS_REJECTED_FINAL => 'badge--rejected',
        ];
    }

    /**
     * French labels of every status of the model, in workflow order.
     *
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return static::decisionStatusLabels();
    }

    /**
     * @return array<string, string>
     */
    public static function statusBadgeClasses(): array
    {
        return static::decisionStatusBadgeClasses();
    }

    public function getStatusLabelAttribute(): string
    {
        return static::statusLabels()[$this->status] ?? (string) $this->status;
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return static::statusBadgeClasses()[$this->status] ?? 'badge--neutral';
    }
}

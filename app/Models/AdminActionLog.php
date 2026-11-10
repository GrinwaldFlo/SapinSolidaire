<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminActionLog extends Model
{
    use HasFactory, HasUuids;

    public const ACTION_FAMILY_VALIDATED = 'family_validated';

    public const ACTION_CHILD_VALIDATED = 'child_validated';

    public const ACTION_FAMILY_STATUS_RESET = 'family_status_reset';

    public const ACTION_FAMILY_EXCLUDED = 'family_excluded';

    public const ACTION_EMAIL_SENT = 'email_sent';

    public const ACTION_GIFT_UPDATED = 'gift_updated';

    /**
     * Human readable labels of the action types.
     *
     * @return array<string, string>
     */
    public static function actionLabels(): array
    {
        return [
            self::ACTION_FAMILY_VALIDATED => 'Validation famille',
            self::ACTION_CHILD_VALIDATED => 'Validation enfant',
            self::ACTION_FAMILY_STATUS_RESET => 'Réinitialisation statut famille',
            self::ACTION_FAMILY_EXCLUDED => 'Exclusion famille',
            self::ACTION_EMAIL_SENT => 'Envoi e-mail',
            self::ACTION_GIFT_UPDATED => 'Modification cadeau',
        ];
    }

    /**
     * Record an action made by the authenticated admin.
     */
    public static function record(
        string $actionType,
        string $description,
        ?string $familyId = null,
        ?string $giftRequestId = null,
        ?string $childId = null,
    ): self {
        return self::create([
            'user_id' => auth()->id(),
            'user_label' => auth()->user()?->name,
            'action_type' => $actionType,
            'description' => $description,
            'family_id' => $familyId,
            'gift_request_id' => $giftRequestId,
            'child_id' => $childId,
        ]);
    }

    public function getActionLabelAttribute(): string
    {
        return self::actionLabels()[$this->action_type] ?? $this->action_type;
    }

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'user_label',
        'action_type',
        'description',
        'family_id',
        'gift_request_id',
        'child_id',
    ];

    /**
     * Get the admin user who triggered the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function giftRequest(): BelongsTo
    {
        return $this->belongsTo(GiftRequest::class);
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }
}

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
    public const ACTION_EMAIL_SENT = 'email_sent';

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

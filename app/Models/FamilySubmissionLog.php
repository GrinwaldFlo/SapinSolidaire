<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilySubmissionLog extends Model
{
    use HasFactory, HasUuids;

    public const ACTION_CREATED = 'created';
    public const ACTION_UPDATED = 'updated';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'action_type',
        'family_id',
        'gift_request_id',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function giftRequest(): BelongsTo
    {
        return $this->belongsTo(GiftRequest::class);
    }
}

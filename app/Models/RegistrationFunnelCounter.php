<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Anonymous count of the registrations that reached a step of the family flow in a season.
 */
class RegistrationFunnelCounter extends Model
{
    use HasUuids;

    public const STEP_LINK_REQUESTED = 'link_requested';

    public const STEP_LINK_OPENED = 'link_opened';

    public const STEP_CONSECUTIVE_YEARS_ACCEPTED = 'consecutive_years_accepted';

    public const STEP_PICKUP_ACCEPTED = 'pickup_accepted';

    public const STEP_CITY_ACCEPTED = 'city_accepted';

    public const STEP_FAMILY_INFO_COMPLETED = 'family_info_completed';

    public const STEP_PROOF_COMPLETED = 'proof_completed';

    public const STEP_ANONYMOUS_CHOICE_MADE = 'anonymous_choice_made';

    public const STEP_CHILDREN_COMPLETED = 'children_completed';

    public const STEP_SUBMIT_ATTEMPTED = 'submit_attempted';

    public const STEP_SUBMITTED = 'submitted';

    public const BLOCKED_REGISTRATION_CLOSED = 'blocked_registration_closed';

    public const BLOCKED_CONSECUTIVE_YEARS = 'blocked_consecutive_years';

    public const BLOCKED_EXCLUDED = 'blocked_excluded';

    public $timestamps = false;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'season_id',
        'step',
        'count',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'count' => 'integer',
    ];

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }
}

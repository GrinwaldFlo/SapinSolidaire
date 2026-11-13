<?php

namespace App\Livewire\Admin;

use App\Livewire\Admin\Concerns\SendsRejectionEmail;
use App\Models\Child;
use App\Models\GiftRequest;
use App\Models\Season;
use App\Models\SentMailLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Families and children of the active season waiting for a correction (status "Refusé"):
 * the selected ones receive their correction request again, with a new 48h access link.
 */
class CorrectionReminders extends Component
{
    use SendsRejectionEmail;

    public const LIST_REQUESTS = 'requests';

    public const LIST_CHILDREN = 'children';

    #[Locked]
    public ?Season $activeSeason = null;

    /**
     * Selected ids per list (sent by the browser: only used to filter the server-side queries).
     *
     * @var array<int, string>
     */
    public array $selectedRequestIds = [];

    /**
     * @var array<int, string>
     */
    public array $selectedChildIds = [];

    public function mount(): void
    {
        $this->activeSeason = Season::getActive();
    }

    /**
     * Requests of the active season sent back to the family for a correction.
     */
    protected function rejectedRequestsQuery(): Builder
    {
        return GiftRequest::where('season_id', $this->activeSeason?->id)
            ->where('status', GiftRequest::STATUS_REJECTED);
    }

    /**
     * Children of the active season waiting for a correction (requests finally rejected excluded).
     */
    protected function rejectedChildrenQuery(): Builder
    {
        return Child::where('status', Child::STATUS_REJECTED)
            ->whereHas('giftRequest', fn (Builder $q) => $q
                ->where('season_id', $this->activeSeason?->id)
                ->where('status', '!=', GiftRequest::STATUS_REJECTED_FINAL));
    }

    /**
     * Eager load of a request: family and date of the last change made by the family.
     */
    protected static function withRequestDetails(Builder|Relation $query): Builder|Relation
    {
        return $query->with('family')
            ->withMax('submissionLogs as last_family_edit_at', 'created_at')
            ->withCasts(['last_family_edit_at' => 'datetime']);
    }

    protected function rejectedRequests(): Collection
    {
        return self::withRequestDetails($this->rejectedRequestsQuery())
            ->with('children')
            ->orderBy('status_changed_at')
            ->get();
    }

    protected function rejectedChildren(): Collection
    {
        return $this->rejectedChildrenQuery()
            ->with(['giftRequest' => fn ($q) => self::withRequestDetails($q)])
            ->orderBy('status_changed_at')
            ->get();
    }

    /**
     * Select every line of a list, or clear the selection when all are already selected.
     */
    public function toggleAll(string $list): void
    {
        $property = $this->selectionProperty($list);

        if ($property === null) {
            return;
        }

        $query = $list === self::LIST_REQUESTS ? $this->rejectedRequestsQuery() : $this->rejectedChildrenQuery();
        $allIds = $query->pluck('id')->map(fn ($id) => (string) $id)->all();

        $this->{$property} = array_diff($allIds, $this->{$property}) === [] ? [] : $allIds;
    }

    protected function selectionProperty(string $list): ?string
    {
        return match ($list) {
            self::LIST_REQUESTS => 'selectedRequestIds',
            self::LIST_CHILDREN => 'selectedChildIds',
            default => null,
        };
    }

    /**
     * Whether a list can receive reminders: the family must still be able to modify its request.
     */
    public function getCanSendProperty(): bool
    {
        return $this->activeSeason !== null && $this->activeSeason->canModify();
    }

    /**
     * Send the correction request again (same comments, new 48h link): one email per family,
     * gathering the family comment and the comments of its selected children.
     */
    public function sendReminders(): void
    {
        if (! $this->can_send) {
            return;
        }

        $requests = $this->rejectedRequestsQuery()->whereKey($this->selectedRequestIds)->with('family')->get()->keyBy('id');
        $childrenByRequest = $this->rejectedChildrenQuery()->whereKey($this->selectedChildIds)
            ->with('giftRequest.family')
            ->orderBy('first_name')
            ->get()
            ->groupBy('gift_request_id');

        $requestIds = $requests->keys()->merge($childrenByRequest->keys())->unique();

        foreach ($requestIds as $requestId) {
            $request = $requests->get($requestId) ?? $childrenByRequest->get($requestId)->first()->giftRequest;
            $comments = [];

            if ($requests->has($requestId) && filled($request->rejection_comment)) {
                $comments[] = $request->rejection_comment;
            }

            foreach ($childrenByRequest->get($requestId, []) as $child) {
                $comments[] = self::childRejectionComment($child->first_name, (string) $child->rejection_comment);
            }

            $this->sendCorrectionEmail($request, self::combineRejectionComments($comments), SentMailLog::PURPOSE_CORRECTION_REMINDER);
        }

        $this->selectedRequestIds = [];
        $this->selectedChildIds = [];

        if ($requestIds->isEmpty()) {
            session()->flash('error', 'Aucune ligne sélectionnée.');

            return;
        }

        session()->flash('message', "Rappel envoyé à {$requestIds->count()} famille(s), avec un nouveau lien valable 48h.");
    }

    public function render()
    {
        $requests = $this->activeSeason ? $this->rejectedRequests() : new Collection;
        $children = $this->activeSeason ? $this->rejectedChildren() : new Collection;

        return view('livewire.admin.correction-reminders', [
            'requests' => $requests,
            'children' => $children,
            'allRequestsSelected' => $requests->isNotEmpty() && $requests->pluck('id')->diff($this->selectedRequestIds)->isEmpty(),
            'allChildrenSelected' => $children->isNotEmpty() && $children->pluck('id')->diff($this->selectedChildIds)->isEmpty(),
            'selectedCount' => count($this->selectedRequestIds) + count($this->selectedChildIds),
        ]);
    }
}

<?php

namespace App\Livewire\Admin;

use App\Livewire\Admin\Concerns\SendsRejectionEmail;
use App\Models\Child;
use App\Models\GiftRequest;
use App\Models\Season;
use App\Models\SentMailLog;
use App\Services\SeasonService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
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

    /**
     * Active season, read again on every request (deadline or active season may change while the page is open).
     */
    #[Computed]
    public function activeSeason(): ?Season
    {
        return Season::getActive();
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
     * Only lines with a comment to send again (a reminder without comment would not tell the family what to correct).
     */
    protected static function withComment(Builder $query): Builder
    {
        return $query->whereNotNull('rejection_comment')->where('rejection_comment', '!=', '');
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

        $query = self::withComment($list === self::LIST_REQUESTS ? $this->rejectedRequestsQuery() : $this->rejectedChildrenQuery());
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
     * Reason why no reminder can be sent (no active season, registrations blocked by an admin
     * or modification deadline passed: the family could not correct its request), or null.
     */
    #[Computed]
    public function sendBlockedMessage(): ?string
    {
        if ($this->activeSeason === null) {
            return null;
        }

        return app(SeasonService::class)->getRegistrationClosedMessage($this->activeSeason, hasRequest: true);
    }

    public function getCanSendProperty(): bool
    {
        return $this->activeSeason !== null && $this->sendBlockedMessage === null;
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

        $requestIds = collect($this->selectedRequestIds)
            ->merge(Child::whereKey($this->selectedChildIds)->pluck('gift_request_id'))
            ->map(fn ($id) => (string) $id)
            ->unique();

        if ($requestIds->isEmpty()) {
            session()->flash('error', 'Aucune ligne sélectionnée.');

            return;
        }

        $sent = $requestIds->filter(fn (string $requestId) => $this->sendReminder($requestId))->count();

        $this->selectedRequestIds = [];
        $this->selectedChildIds = [];

        if ($sent === 0) {
            session()->flash('error', 'Aucun rappel envoyé : les lignes sélectionnées ont été corrigées entre-temps par les familles.');

            return;
        }

        session()->flash('message', "Rappel envoyé à {$sent} famille(s), avec un nouveau lien valable 48h.");
    }

    /**
     * Send the reminder of one family. The comment is built under a lock of its request (a request
     * or child corrected by the family in the meantime is no longer refused and is not reminded);
     * the new token and the email are created once the transaction is committed.
     */
    protected function sendReminder(string $requestId): bool
    {
        $reminder = DB::transaction(function () use ($requestId): ?array {
            $request = GiftRequest::where('season_id', $this->activeSeason->id)
                ->with('family')
                ->lockForUpdate()
                ->find($requestId);

            if ($request === null) {
                return null;
            }

            $comments = [];

            if (in_array($requestId, $this->selectedRequestIds, true)
                && $request->status === GiftRequest::STATUS_REJECTED
                && filled($request->rejection_comment)) {
                $comments[] = $request->rejection_comment;
            }

            $children = self::withComment($this->rejectedChildrenQuery())
                ->where('gift_request_id', $requestId)
                ->whereKey($this->selectedChildIds)
                ->orderBy('first_name')
                ->lockForUpdate()
                ->get();

            foreach ($children as $child) {
                $comments[] = self::childRejectionComment($child->first_name, $child->rejection_comment);
            }

            return $comments === [] ? null : [$request, self::combineRejectionComments($comments)];
        });

        if ($reminder === null) {
            return false;
        }

        [$request, $comment] = $reminder;
        $this->sendCorrectionEmail($request, $comment, SentMailLog::PURPOSE_CORRECTION_REMINDER);

        return true;
    }

    /**
     * Whether every selectable line (with a comment to send) of a list is selected.
     *
     * @param  array<int, string>  $selectedIds
     */
    protected static function allSelected(Collection $lines, array $selectedIds): bool
    {
        $selectableIds = $lines->filter(fn ($line) => filled($line->rejection_comment))->pluck('id');

        return $selectableIds->isNotEmpty() && $selectableIds->diff($selectedIds)->isEmpty();
    }

    public function render()
    {
        $requests = $this->activeSeason ? $this->rejectedRequests() : new Collection;
        $children = $this->activeSeason ? $this->rejectedChildren() : new Collection;

        return view('livewire.admin.correction-reminders', [
            'activeSeason' => $this->activeSeason,
            'requests' => $requests,
            'children' => $children,
            'allRequestsSelected' => self::allSelected($requests, $this->selectedRequestIds),
            'allChildrenSelected' => self::allSelected($children, $this->selectedChildIds),
            // Only the selected lines still listed (a line corrected in the meantime is no longer sent)
            'selectedCount' => $requests->pluck('id')->intersect($this->selectedRequestIds)->count()
                + $children->pluck('id')->intersect($this->selectedChildIds)->count(),
        ]);
    }
}

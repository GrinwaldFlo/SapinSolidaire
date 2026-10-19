<?php

namespace App\Livewire\Admin;

use App\Livewire\Admin\Concerns\InsertsCommentTemplates;
use App\Models\AdminActionLog;
use App\Models\Child;
use App\Models\Family;
use App\Models\GiftRequest;
use App\Models\Season;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class FamilyManagement extends Component
{
    use InsertsCommentTemplates, WithPagination;

    private const SORTABLE_COLUMNS = [
        'first_name',
        'last_name',
        'email',
        'phone',
    ];

    public ?Season $activeSeason = null;

    public string $search = '';

    public string $statusFilter = '';

    public string $sortBy = 'last_name';

    public string $sortDirection = 'asc';

    public array $predefinedRejectionMessages = [];

    /**
     * @var array<string, string> Exclusion reason being typed, keyed by family id
     */
    public array $exclusionReasons = [];

    public function mount(): void
    {
        $this->activeSeason = Season::getActive();
        $this->predefinedRejectionMessages = Setting::getValidationCommentTemplates();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function sort(string $column): void
    {
        if (! in_array($column, self::SORTABLE_COLUMNS, true)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function insertExclusionTemplate(string $familyId, string $messageKey): void
    {
        $template = $this->resolveTemplateMessage($messageKey);
        if ($template === '') {
            return;
        }

        $this->exclusionReasons[$familyId] = $this->appendTemplateMessage($this->exclusionReasons[$familyId] ?? '', $template);
        $this->resetErrorBag(self::exclusionReasonKey($familyId));
    }

    /**
     * @return bool Whether the action was applied
     */
    public function applyAction(string $familyId, string $action = ''): bool
    {
        return match ($action) {
            'reset_pending' => $this->resetToPending($familyId),
            'exclude' => $this->excludeFamily($familyId, (string) ($this->exclusionReasons[$familyId] ?? '')),
            default => false,
        };
    }

    public static function exclusionReasonKey(string $familyId): string
    {
        return 'exclusionReasons.'.$familyId;
    }

    /**
     * Lock the family row and its active-season request (if any) for update.
     *
     * @return array{0: ?Family, 1: ?GiftRequest}
     */
    private function lockFamilyAndActiveRequest(string $familyId): array
    {
        $family = Family::lockForUpdate()->find($familyId);

        if (! $family || ! $this->activeSeason) {
            return [$family, null];
        }

        $request = GiftRequest::with('children')
            ->where('family_id', $family->id)
            ->where('season_id', $this->activeSeason->id)
            ->lockForUpdate()
            ->first();

        return [$family, $request];
    }

    private function resetToPending(string $familyId): bool
    {
        [$family, $request] = DB::transaction(function () use ($familyId) {
            [$family, $request] = $this->lockFamilyAndActiveRequest($familyId);

            if (! $family || (! $request && ! $family->isExcluded())) {
                return [null, null];
            }

            if ($request) {
                $request->resetToPending();

                foreach ($request->children as $child) {
                    $child->resetToPending();
                }
            }

            $family->clearExclusion();

            return [$family, $request];
        });

        if (! $family instanceof Family) {
            return false;
        }

        $this->logAction(
            AdminActionLog::ACTION_FAMILY_STATUS_RESET,
            ($request ? 'Statut famille remis en attente: ' : 'Exclusion levée: ').$family->full_name,
            $family,
            $request
        );

        return true;
    }

    private function excludeFamily(string $familyId, string $reason): bool
    {
        $reason = trim($reason);

        if ($reason === '') {
            $this->addError(self::exclusionReasonKey($familyId), 'Un motif est obligatoire pour exclure une famille.');

            return false;
        }

        [$family, $request] = DB::transaction(function () use ($familyId, $reason) {
            [$family, $request] = $this->lockFamilyAndActiveRequest($familyId);

            if (! $family) {
                return [null, null];
            }

            if ($request) {
                $request->setRelation('family', $family);
                $request->setStatus(GiftRequest::STATUS_REJECTED_FINAL, $reason);

                foreach ($request->children as $child) {
                    $child->setStatus(Child::STATUS_REJECTED_FINAL, $reason);
                }
            } else {
                $family->exclude($reason);
            }

            return [$family, $request];
        });

        if (! $family instanceof Family) {
            return false;
        }

        unset($this->exclusionReasons[$familyId]);

        $this->logAction(
            AdminActionLog::ACTION_FAMILY_EXCLUDED,
            'Famille exclue: '.$family->full_name.' — Motif: '.$reason,
            $family,
            $request
        );

        return true;
    }

    private function logAction(string $type, string $description, Family $family, ?GiftRequest $request): void
    {
        AdminActionLog::create([
            'user_id' => auth()->id(),
            'user_label' => auth()->user()?->name,
            'action_type' => $type,
            'description' => $description,
            'family_id' => $family->id,
            'gift_request_id' => $request?->id,
        ]);
    }

    public function render()
    {
        $query = Family::with(['giftRequests.season', 'giftRequests.children']);

        if ($this->statusFilter) {
            $query->where(function ($q) {
                $q->whereHas('giftRequests', function ($q) {
                    $q->where('status', $this->statusFilter);
                });

                if ($this->statusFilter === GiftRequest::STATUS_REJECTED_FINAL) {
                    $q->orWhereNotNull('excluded_at');
                }
            });
        }

        if ($this->search) {
            $search = '%'.trim($this->search).'%';
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', $search)
                    ->orWhere('first_name', 'like', $search)
                    ->orWhere('last_name', 'like', $search)
                    ->orWhere('phone', 'like', $search)
                    ->orWhere('street_name', 'like', $search)
                    ->orWhere('house_no', 'like', $search)
                    ->orWhere('postal_code', 'like', $search)
                    ->orWhere('city', 'like', $search)
                    ->orWhereHas('giftRequests.children', function ($q) use ($search) {
                        $q->where('first_name', 'like', $search);
                    });
            });
        }

        return view('livewire.admin.family-management', [
            'families' => $query->orderBy($this->sortBy, $this->sortDirection)->paginate(200),
            'statuses' => [
                GiftRequest::STATUS_PENDING => 'À valider',
                GiftRequest::STATUS_VALIDATED => 'Validé',
                GiftRequest::STATUS_REJECTED => 'Refusé',
                GiftRequest::STATUS_REJECTED_FINAL => 'Refusé définitivement',
            ],
        ]);
    }
}

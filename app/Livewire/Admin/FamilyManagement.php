<?php

namespace App\Livewire\Admin;

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
    use WithPagination;

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

    public function mount(): void
    {
        $this->activeSeason = Season::getActive();
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

    public function applyAction(string $familyId, ?string $giftRequestId = null, string $action = '', string $reason = ''): void
    {
        if (! $giftRequestId) {
            return;
        }

        match ($action) {
            'reset_pending' => $this->resetToPending($giftRequestId),
            'exclude' => $this->excludeFamily($giftRequestId, $reason),
            default => null,
        };
    }

    private function resetToPending(string $giftRequestId): void
    {
        $request = DB::transaction(function () use ($giftRequestId) {
            $request = GiftRequest::with('children')->lockForUpdate()->find($giftRequestId);

            if (! $request) {
                return null;
            }

            $request->resetToPending();

            foreach ($request->children as $child) {
                $child->resetToPending();
            }

            return $request->loadMissing('family');
        });

        if ($request instanceof GiftRequest) {
            $this->logAction(
                AdminActionLog::ACTION_FAMILY_STATUS_RESET,
                'Statut famille remis en attente: '.$request->family?->full_name,
                $request
            );
        }
    }

    private function excludeFamily(string $giftRequestId, string $reason): void
    {
        $reason = trim($reason);

        if ($reason === '') {
            $this->addError('exclusionReason', 'Un motif est obligatoire pour exclure une famille.');

            return;
        }

        $request = DB::transaction(function () use ($giftRequestId, $reason) {
            $request = GiftRequest::with('children')->lockForUpdate()->find($giftRequestId);

            if (! $request) {
                return null;
            }

            $request->setStatus(GiftRequest::STATUS_REJECTED_FINAL, $reason);

            foreach ($request->children as $child) {
                $child->setStatus(Child::STATUS_REJECTED_FINAL, $reason);
            }

            return $request->loadMissing('family');
        });

        if ($request instanceof GiftRequest) {
            $this->logAction(
                AdminActionLog::ACTION_FAMILY_EXCLUDED,
                'Famille exclue: '.$request->family?->full_name.' — Motif: '.$reason,
                $request
            );
        }
    }

    private function logAction(string $type, string $description, GiftRequest $request): void
    {
        AdminActionLog::create([
            'user_id' => auth()->id(),
            'user_label' => auth()->user()?->name,
            'action_type' => $type,
            'description' => $description,
            'family_id' => $request->family_id,
            'gift_request_id' => $request->id,
        ]);
    }

    public function render()
    {
        $query = Family::with(['giftRequests.season', 'giftRequests.children']);

        if ($this->statusFilter) {
            $query->whereHas('giftRequests', function ($q) {
                $q->where('status', $this->statusFilter);
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
            'rejectionTemplates' => Setting::getValidationCommentTemplates(),
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

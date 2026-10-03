<?php

namespace App\Livewire\Admin;

use App\Models\Family;
use App\Models\GiftRequest;
use App\Models\Season;
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

    public function applyAction(string $familyId, ?string $giftRequestId = null, string $action = ''): void
    {
        if ($action !== 'reset_pending' || ! $giftRequestId) {
            return;
        }

        DB::transaction(function () use ($giftRequestId) {
            $request = GiftRequest::with('children')->lockForUpdate()->find($giftRequestId);

            if (! $request) {
                return;
            }

            $request->resetToPending();

            foreach ($request->children as $child) {
                $child->resetToPending();
            }
        });
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

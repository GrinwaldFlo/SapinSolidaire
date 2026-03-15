<?php

namespace App\Livewire\Admin;

use App\Models\Child;
use App\Models\Season;
use Livewire\Component;
use Livewire\WithPagination;

class ChildrenMonitoring extends Component
{
    use WithPagination;

    public $seasons;
    public ?string $selectedSeasonId = null;
    public string $statusFilter = '';
    public string $search = '';
    public string $sortBy = 'first_name';
    public string $sortDirection = 'asc';

    public function mount(): void
    {
        $this->seasons = Season::orderByDesc('start_date')->get();
        $activeSeason = Season::getActive();
        $this->selectedSeasonId = $activeSeason?->id;
    }

    public function updatedSelectedSeasonId(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function render()
    {
        $children = collect();

        if ($this->selectedSeasonId) {
            $query = Child::with(['giftRequest.family', 'giftRequest.season'])
                ->whereHas('giftRequest', function ($q) {
                    $q->where('season_id', $this->selectedSeasonId);
                });

            if ($this->statusFilter) {
                $query->where('status', $this->statusFilter);
            }

            if ($this->search) {
                $search = $this->search;
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                      ->orWhere('first_name', 'like', "%{$search}%")
                      ->orWhere('gift', 'like', "%{$search}%")
                      ->orWhereHas('giftRequest.family', function ($q) use ($search) {
                          $q->where('last_name', 'like', "%{$search}%");
                      });
                });
            }

            if ($this->sortBy === 'family_name') {
                $query->join('gift_requests', 'children.gift_request_id', '=', 'gift_requests.id')
                      ->join('families', 'gift_requests.family_id', '=', 'families.id')
                      ->orderBy('families.last_name', $this->sortDirection)
                      ->select('children.*');
            } else {
                $query->orderBy($this->sortBy, $this->sortDirection);
            }

            $children = $query->paginate(100);
        }

        return view('livewire.admin.children-monitoring', [
            'children' => $children,
            'statuses' => [
                Child::STATUS_PENDING => 'À valider',
                Child::STATUS_VALIDATED => 'Validé',
                Child::STATUS_REJECTED => 'Refusé',
                Child::STATUS_REJECTED_FINAL => 'Refusé définitivement',
                Child::STATUS_PRINTED => 'Imprimé',
                Child::STATUS_RECEIVED => 'Reçu',
                Child::STATUS_GIVEN => 'Donné',
            ],
        ]);
    }
}

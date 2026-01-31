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
    public ?int $selectedSeasonId = null;
    public string $statusFilter = '';

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

            $children = $query->orderBy('first_name')->paginate(20);
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

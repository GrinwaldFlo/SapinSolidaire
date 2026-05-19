<?php

namespace App\Livewire\Admin;

use App\Models\Family;
use App\Models\Season;
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

    public function render()
    {
        $query = Family::with(['giftRequests.season', 'giftRequests.children']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('email', 'like', "%{$this->search}%")
                    ->orWhere('first_name', 'like', "%{$this->search}%")
                    ->orWhere('last_name', 'like', "%{$this->search}%");
            });
        }

        return view('livewire.admin.family-management', [
            'families' => $query->orderBy($this->sortBy, $this->sortDirection)->paginate(200),
        ]);
    }
}

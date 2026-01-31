<?php

namespace App\Livewire\Admin;

use App\Models\Family;
use App\Models\Season;
use Livewire\Component;
use Livewire\WithPagination;

class FamilyManagement extends Component
{
    use WithPagination;

    public ?Season $activeSeason = null;
    public string $search = '';

    public function mount(): void
    {
        $this->activeSeason = Season::getActive();
    }

    public function updatedSearch(): void
    {
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
            'families' => $query->orderBy('last_name')->paginate(20),
        ]);
    }
}

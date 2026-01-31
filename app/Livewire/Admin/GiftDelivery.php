<?php

namespace App\Livewire\Admin;

use App\Models\Child;
use App\Models\Season;
use Livewire\Component;
use Livewire\WithPagination;

class GiftDelivery extends Component
{
    use WithPagination;

    public ?Season $activeSeason = null;
    public ?Child $selectedChild = null;

    public function mount(): void
    {
        $this->activeSeason = Season::getActive();
    }

    public function selectChild(int $childId): void
    {
        $this->selectedChild = Child::with('giftRequest.family')->findOrFail($childId);
    }

    public function clearSelection(): void
    {
        $this->selectedChild = null;
    }

    public function markAsGiven(int $childId): void
    {
        $child = Child::findOrFail($childId);
        $child->setStatus(Child::STATUS_GIVEN);
        $this->selectedChild = null;
    }

    public function render()
    {
        $children = collect();

        if ($this->activeSeason) {
            $children = Child::with('giftRequest.family')
                ->whereHas('giftRequest', function ($q) {
                    $q->where('season_id', $this->activeSeason->id);
                })
                ->where('status', Child::STATUS_RECEIVED)
                ->orderBy('first_name')
                ->paginate(20);
        }

        return view('livewire.admin.gift-delivery', [
            'children' => $children,
        ]);
    }
}

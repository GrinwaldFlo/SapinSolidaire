<?php

namespace App\Livewire\Admin;

use App\Models\Child;
use App\Models\Family;
use App\Models\GiftRequest;
use App\Models\Season;
use Illuminate\Support\Collection;
use Livewire\Component;

class GiftDelivery extends Component
{
    public ?Season $activeSeason = null;
    public string $searchName = '';
    public ?string $selectedFamilyId = null;
    public bool $showMobileDetail = false;

    public function mount(): void
    {
        $this->activeSeason = Season::getActive();
    }

    public function updatedSearchName(): void
    {
        $this->selectedFamilyId = null;
        $this->showMobileDetail = false;
    }

    public function selectFamily(string $familyId): void
    {
        $this->selectedFamilyId = $familyId;
        $this->showMobileDetail = true;
    }

    public function closeMobileDetail(): void
    {
        $this->showMobileDetail = false;
    }

    public function clearFilter(): void
    {
        $this->searchName = '';
        $this->selectedFamilyId = null;
        $this->showMobileDetail = false;
    }

    public function markAsGiven(string $childId): void
    {
        $child = Child::findOrFail($childId);
        $child->setStatus(Child::STATUS_GIVEN);
    }

    public function markAllAsGiven(string $familyId): void
    {
        if (!$this->activeSeason) {
            return;
        }

        $children = Child::whereHas('giftRequest', function ($q) use ($familyId) {
            $q->where('season_id', $this->activeSeason->id)
              ->where('family_id', $familyId);
        })
            ->where('status', Child::STATUS_RECEIVED)
            ->get();

        foreach ($children as $child) {
            $child->setStatus(Child::STATUS_GIVEN);
        }
    }

    public function render()
    {
        $families = collect();
        $selectedFamily = null;
        $selectedChildren = collect();

        if ($this->activeSeason && $this->searchName !== '') {
            $families = Family::where('last_name', 'like', '%' . $this->searchName . '%')
                ->whereHas('giftRequests', function ($q) {
                    $q->where('season_id', $this->activeSeason->id);
                })
                ->whereHas('giftRequests.children', function ($q) {
                    $q->where('status', Child::STATUS_RECEIVED);
                })
                ->orderBy('last_name')
                ->get();
        }

        if ($this->selectedFamilyId) {
            $selectedFamily = Family::find($this->selectedFamilyId);

            if ($selectedFamily && $this->activeSeason) {
                $selectedChildren = Child::with('giftRequest')
                    ->whereHas('giftRequest', function ($q) {
                        $q->where('season_id', $this->activeSeason->id)
                          ->where('family_id', $this->selectedFamilyId);
                    })
                    ->where('status', Child::STATUS_RECEIVED)
                    ->orderBy('child_number')
                    ->get();
            }
        }

        return view('livewire.admin.gift-delivery', [
            'families' => $families,
            'selectedFamily' => $selectedFamily,
            'selectedChildren' => $selectedChildren,
        ]);
    }
}

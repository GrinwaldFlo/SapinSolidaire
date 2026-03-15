<?php

namespace App\Livewire\Admin;

use App\Models\Child;
use App\Models\Season;
use Livewire\Component;

class GiftReception extends Component
{
    public ?Season $activeSeason = null;
    public ?Child $selectedChild = null;
    public string $familyNumber = '';
    public bool $showMobileDetail = false;

    public function mount(): void
    {
        $this->activeSeason = Season::getActive();
    }

    public function updatedFamilyNumber(): void
    {
        $this->selectedChild = null;
        $this->showMobileDetail = false;
    }

    public function appendDigit(string $digit): void
    {
        $this->familyNumber .= $digit;
        $this->selectedChild = null;
        $this->showMobileDetail = false;
    }

    public function backspace(): void
    {
        $this->familyNumber = substr($this->familyNumber, 0, -1);
        $this->selectedChild = null;
        $this->showMobileDetail = false;
    }

    public function clearFilter(): void
    {
        $this->familyNumber = '';
        $this->selectedChild = null;
        $this->showMobileDetail = false;
    }

    public function selectChild(string $childId): void
    {
        $this->selectedChild = Child::with('giftRequest.family')->findOrFail($childId);
        $this->showMobileDetail = true;
    }

    public function closeMobileDetail(): void
    {
        $this->showMobileDetail = false;
    }

    public function clearSelection(): void
    {
        $this->selectedChild = null;
        $this->showMobileDetail = false;
    }

    public function markAsReceived(string $childId): void
    {
        $child = Child::findOrFail($childId);
        $child->setStatus(Child::STATUS_RECEIVED);
        $this->selectedChild = null;
        $this->showMobileDetail = false;
    }

    public function render()
    {
        $children = collect();

        if ($this->activeSeason && $this->familyNumber !== '') {
            $familyNum = (int) $this->familyNumber;
            $children = Child::with('giftRequest.family')
                ->whereHas('giftRequest', function ($q) use ($familyNum) {
                    $q->where('season_id', $this->activeSeason->id)
                      ->where('family_number', $familyNum);
                })
                ->where('status', Child::STATUS_PRINTED)
                ->orderBy('child_number')
                ->get();
        }

        return view('livewire.admin.gift-reception', [
            'children' => $children,
        ]);
    }
}

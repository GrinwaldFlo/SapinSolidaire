<?php

namespace App\Livewire\Admin;

use App\Models\Child;
use App\Models\Family;
use App\Models\GiftRequest;
use App\Models\Season;
use Livewire\Component;

class Dashboard extends Component
{
    public ?Season $activeSeason = null;
    public int $totalFamilies = 0;
    public int $totalChildren = 0;
    public int $pendingFamilies = 0;
    public int $pendingChildren = 0;
    public int $validatedChildren = 0;
    public int $printedChildren = 0;
    public int $receivedChildren = 0;
    public int $givenChildren = 0;

    public function mount(): void
    {
        $this->activeSeason = Season::getActive();
        $this->loadStats();
    }

    protected function loadStats(): void
    {
        if (! $this->activeSeason) {
            return;
        }

        $this->totalFamilies = GiftRequest::where('season_id', $this->activeSeason->id)->count();

        $this->totalChildren = Child::whereHas('giftRequest', function ($q) {
            $q->where('season_id', $this->activeSeason->id);
        })->count();

        $this->pendingFamilies = GiftRequest::where('season_id', $this->activeSeason->id)
            ->where('status', GiftRequest::STATUS_PENDING)
            ->count();

        $childrenQuery = Child::whereHas('giftRequest', function ($q) {
            $q->where('season_id', $this->activeSeason->id);
        });

        $this->pendingChildren = (clone $childrenQuery)->where('status', Child::STATUS_PENDING)->count();
        $this->validatedChildren = (clone $childrenQuery)->where('status', Child::STATUS_VALIDATED)->count();
        $this->printedChildren = (clone $childrenQuery)->where('status', Child::STATUS_PRINTED)->count();
        $this->receivedChildren = (clone $childrenQuery)->where('status', Child::STATUS_RECEIVED)->count();
        $this->givenChildren = (clone $childrenQuery)->where('status', Child::STATUS_GIVEN)->count();
    }

    public function render()
    {
        return view('livewire.admin.dashboard');
    }
}

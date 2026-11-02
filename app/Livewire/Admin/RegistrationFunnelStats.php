<?php

namespace App\Livewire\Admin;

use App\Models\Season;
use App\Services\RegistrationFunnel;
use Livewire\Component;

class RegistrationFunnelStats extends Component
{
    public ?string $selectedSeasonId = null;

    public function mount(): void
    {
        $this->selectedSeasonId = Season::getActive()?->id
            ?? Season::orderByDesc('start_date')->value('id');
    }

    public function render(RegistrationFunnel $funnel)
    {
        $season = $this->selectedSeasonId ? Season::find($this->selectedSeasonId) : null;

        return view('livewire.admin.registration-funnel-stats', [
            'seasons' => Season::orderByDesc('start_date')->get(),
            'report' => $season ? $funnel->report($season) : null,
        ]);
    }
}

<?php

namespace App\Livewire\Admin;

use App\Models\Child;
use App\Models\Season;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Component;

class LabelGeneration extends Component
{
    public ?Season $activeSeason = null;
    public int $validatedCount = 0;

    public function mount(): void
    {
        $this->activeSeason = Season::getActive();
        $this->loadCount();
    }

    protected function loadCount(): void
    {
        if (! $this->activeSeason) {
            return;
        }

        $this->validatedCount = Child::whereHas('giftRequest', function ($q) {
            $q->where('season_id', $this->activeSeason->id);
        })->where('status', Child::STATUS_VALIDATED)->count();
    }

    public function generatePdf()
    {
        if (! $this->activeSeason || $this->validatedCount === 0) {
            return;
        }

        // Get validated children ordered by validation date
        $children = Child::with('giftRequest.family')
            ->whereHas('giftRequest', function ($q) {
                $q->where('season_id', $this->activeSeason->id);
            })
            ->where('status', Child::STATUS_VALIDATED)
            ->orderBy('validated_at')
            ->get();

        // Update status to printed
        foreach ($children as $child) {
            $child->setStatus(Child::STATUS_PRINTED);
        }

        // Generate PDF
        $pdf = Pdf::loadView('pdf.labels', [
            'children' => $children,
        ]);

        $pdf->setPaper('a4');
        $pdf->setOption('margin-top', 10);
        $pdf->setOption('margin-bottom', 10);
        $pdf->setOption('margin-left', 10);
        $pdf->setOption('margin-right', 10);

        $this->loadCount();

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            'etiquettes-'.now()->format('Y-m-d-His').'.pdf'
        );
    }

    public function resetPrintedLabels(): void
    {
        if (! $this->activeSeason) {
            return;
        }

        Child::whereHas('giftRequest', function ($q) {
                $q->where('season_id', $this->activeSeason->id);
            })
            ->where('status', Child::STATUS_PRINTED)
            ->get()
            ->each(fn ($child) => $child->setStatus(Child::STATUS_VALIDATED));

        $this->loadCount();

        session()->flash('message', 'Étiquettes réinitialisées avec succès.');
    }

    public function render()
    {
        return view('livewire.admin.label-generation');
    }
}

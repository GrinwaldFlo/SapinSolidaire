<?php

namespace App\Livewire\Admin;

use App\Mail\RequestCompleteMail;
use App\Models\Child;
use App\Models\GeneratedPdf;
use App\Models\Season;
use App\Models\SentMailLog;
use App\Models\Setting;
use App\Services\FamilyMailer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class LabelGeneration extends Component
{
    public ?Season $activeSeason = null;
    public int $validatedCount = 0;
    public int $validatedFamilyCount = 0;

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

        $query = Child::whereHas('giftRequest', function ($q) {
            $q->where('season_id', $this->activeSeason->id);
        })->where('status', Child::STATUS_VALIDATED);

        $this->validatedCount = (clone $query)->count();
        $this->validatedFamilyCount = (clone $query)->distinct()->count('gift_request_id');
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
        $pdfStyle = Setting::getPdfStyle();
        $view = $pdfStyle === Setting::PDF_STYLE_GRID ? 'pdf.grid' : 'pdf.labels';

        $pdf = Pdf::loadView($view, [
            'children' => $children,
        ]);

        $pdf->setPaper('a4');
        $pdf->setOption('margin-top', 10);
        $pdf->setOption('margin-bottom', 10);
        $pdf->setOption('margin-left', 10);
        $pdf->setOption('margin-right', 10);

        // Save PDF to disk and record history
        $filename = 'cartes-'.now()->format('Y-m-d-His').'.pdf';
        $path = 'generated-pdfs/'.$this->activeSeason->id.'/'.$filename;

        Storage::disk('local')->put($path, $pdf->output());

        GeneratedPdf::create([
            'season_id' => $this->activeSeason->id,
            'user_id' => Auth::id(),
            'filename' => $filename,
            'path' => $path,
            'children_count' => $children->count(),
            'style' => $pdfStyle,
        ]);

        $sentCount = $this->sendRequestCompleteMails($children);

        $this->loadCount();

        session()->flash('message', "PDF généré pour {$children->count()} enfant(s). {$sentCount} e-mail(s) envoyé(s) aux familles.");

        return response()->streamDownload(
            fn () => print (Storage::disk('local')->get($path)),
            $filename
        );
    }

    /**
     * Send one "request complete" mail per family, listing all of its children just printed.
     *
     * @param  Collection<int, Child>  $children
     */
    protected function sendRequestCompleteMails(Collection $children): int
    {
        $mailer = app(FamilyMailer::class);
        $sent = 0;

        foreach ($children->groupBy('gift_request_id') as $familyChildren) {
            $giftRequest = $familyChildren->first()->giftRequest;

            if (! $giftRequest?->family?->email) {
                continue;
            }

            $mailer->queueForRequest(
                $giftRequest,
                new RequestCompleteMail($giftRequest, $this->activeSeason, $familyChildren->values()),
                SentMailLog::PURPOSE_REQUEST_COMPLETE
            );
            $sent++;
        }

        return $sent;
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

        session()->flash('message', 'Cartes réinitialisées avec succès.');
    }

    public function render()
    {
        $generatedPdfs = $this->activeSeason
            ? GeneratedPdf::with('user')
                ->where('season_id', $this->activeSeason->id)
                ->orderByDesc('created_at')
                ->get()
            : collect();

        return view('livewire.admin.label-generation', [
            'generatedPdfs' => $generatedPdfs,
        ]);
    }
}

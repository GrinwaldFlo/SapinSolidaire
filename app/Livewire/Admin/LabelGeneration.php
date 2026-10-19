<?php

namespace App\Livewire\Admin;

use App\Mail\RequestCompleteMail;
use App\Models\Child;
use App\Models\GeneratedPdf;
use App\Models\GiftRequest;
use App\Models\Season;
use App\Models\SentMailLog;
use App\Models\Setting;
use App\Services\FamilyMailer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class LabelGeneration extends Component
{
    public ?Season $activeSeason = null;

    public int $validatedCount = 0;

    public int $requestCompleteMailCount = 0;

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

        // Families that will receive the "request complete" mail once the validated children are printed
        $this->requestCompleteMailCount = $this->requestsWithChildrenInStatus(Child::STATUS_VALIDATED)
            ->filter(fn (GiftRequest $request) => $request->isRequestCompleteMailDue([Child::STATUS_VALIDATED]))
            ->count();
    }

    /**
     * Active-season requests having at least one child in the given status, with family and children loaded.
     *
     * @param  array<int, string>  $giftRequestIds  Restrict to these requests when not empty
     * @return Collection<int, GiftRequest>
     */
    protected function requestsWithChildrenInStatus(string $childStatus, array $giftRequestIds = []): Collection
    {
        return GiftRequest::with(['family', 'children'])
            ->where('season_id', $this->activeSeason->id)
            ->when($giftRequestIds !== [], fn ($q) => $q->whereIn('id', $giftRequestIds))
            ->whereHas('children', fn ($q) => $q->where('status', $childStatus))
            ->get();
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
     * Send the "request complete" mail, at most once, to each family whose children are now all printed.
     *
     * @param  Collection<int, Child>  $printedChildren
     */
    protected function sendRequestCompleteMails(Collection $printedChildren): int
    {
        $mailer = app(FamilyMailer::class);
        $giftRequestIds = $printedChildren->pluck('gift_request_id')->unique()->values()->all();
        $sent = 0;

        foreach ($this->requestsWithChildrenInStatus(Child::STATUS_PRINTED, $giftRequestIds) as $giftRequest) {
            if (! $giftRequest->isRequestCompleteMailDue() || ! $giftRequest->markRequestCompleteMailSent()) {
                continue;
            }

            $mailer->queueForRequest(
                $giftRequest,
                new RequestCompleteMail(
                    $giftRequest,
                    $this->activeSeason,
                    $giftRequest->children->whereIn('status', GiftRequest::PRINTED_CHILD_STATUSES)->values()
                ),
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

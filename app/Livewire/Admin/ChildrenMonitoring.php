<?php

namespace App\Livewire\Admin;

use App\Models\Child;
use App\Models\Season;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

class ChildrenMonitoring extends Component
{
    use WithPagination;

    private const SORTABLE_COLUMNS = [
        'first_name',
        'code',
        'gift',
        'status',
        'family_name',
    ];

    public $seasons;
    public ?string $selectedSeasonId = null;
    public string $statusFilter = '';
    public string $search = '';
    public string $sortBy = 'first_name';
    public string $sortDirection = 'asc';

    public bool $showFamilyModal = false;
    public ?array $selectedFamily = null;

    public function mount(): void
    {
        $this->seasons = Season::orderByDesc('start_date')->get();
        $activeSeason = Season::getActive();
        $this->selectedSeasonId = $activeSeason?->id;
    }

    public function updatedSelectedSeasonId(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
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

    public function showFamilyDetails(string $familyId): void
    {
        $family = \App\Models\Family::find($familyId);

        if (! $family) {
            return;
        }

        $children = collect();
        if ($this->selectedSeasonId) {
            $giftRequest = $family->giftRequests()->where('season_id', $this->selectedSeasonId)->first();
            if ($giftRequest) {
                $children = $giftRequest->children()->orderBy('first_name')->get()
                    ->map(fn ($child) => [
                        'first_name' => $child->first_name,
                        'formatted_age' => $child->formatted_age,
                        'gender_label' => $child->gender !== 'unspecified' ? $child->gender_label : null,
                        'gift' => $child->gift,
                    ]);
            }
        }

        $this->selectedFamily = [
            'last_name' => $family->last_name,
            'first_name' => $family->first_name,
            'email' => $family->email,
            'phone' => $family->phone,
            'formatted_phone' => $family->formatted_phone,
            'tel_phone' => $family->tel_phone,
            'full_address' => $family->full_address,
            'children' => $children->toArray(),
        ];

        $this->showFamilyModal = true;
    }

    public function closeFamilyModal(): void
    {
        $this->showFamilyModal = false;
        $this->selectedFamily = null;
    }

    public function exportPdf()
    {
        if (! $this->selectedSeasonId) {
            return;
        }

        $season = $this->seasons->firstWhere('id', $this->selectedSeasonId);

        $query = Child::with(['giftRequest.family', 'giftRequest.season'])
            ->whereHas('giftRequest', function ($q) {
                $q->where('season_id', $this->selectedSeasonId);
            });

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('gift', 'like', "%{$search}%")
                  ->orWhereHas('giftRequest.family', function ($q) use ($search) {
                      $q->where('last_name', 'like', "%{$search}%");
                  });
            });
        }

        if ($this->sortBy === 'family_name') {
            $query->join('gift_requests', 'children.gift_request_id', '=', 'gift_requests.id')
                  ->join('families', 'gift_requests.family_id', '=', 'families.id')
                  ->orderBy('families.last_name', $this->sortDirection)
                  ->select('children.*');
        } else {
            $query->orderBy($this->sortBy, $this->sortDirection);
        }

        $children = $query->get();

        $statusLabels = [
            '' => 'Tous les statuts',
            Child::STATUS_PENDING => 'À valider',
            Child::STATUS_VALIDATED => 'Validé',
            Child::STATUS_REJECTED => 'Refusé',
            Child::STATUS_REJECTED_FINAL => 'Refusé définitivement',
            Child::STATUS_PRINTED => 'Imprimé',
            Child::STATUS_RECEIVED => 'Reçu',
            Child::STATUS_GIVEN => 'Donné',
        ];

        $pdf = Pdf::loadView('pdf.monitoring-grid', [
            'children' => $children,
            'seasonName' => $season?->name ?? '—',
            'statusLabel' => $statusLabels[$this->statusFilter] ?? $this->statusFilter,
            'search' => $this->search,
        ]);

        $pdf->setPaper('a4');
        $pdf->setOption('margin-top', 15);
        $pdf->setOption('margin-bottom', 20);
        $pdf->setOption('margin-left', 10);
        $pdf->setOption('margin-right', 10);

        $filename = 'suivi-enfants-'.now()->format('Y-m-d-His').'.pdf';
        $path = 'exports/'.$filename;

        Storage::disk('local')->put($path, $pdf->output());

        return response()->streamDownload(
            fn () => print (Storage::disk('local')->get($path)),
            $filename
        );
    }

    public function render()
    {
        $children = collect();

        if ($this->selectedSeasonId) {
            $query = Child::with(['giftRequest.family', 'giftRequest.season'])
                ->whereHas('giftRequest', function ($q) {
                    $q->where('season_id', $this->selectedSeasonId);
                });

            if ($this->statusFilter) {
                $query->where('status', $this->statusFilter);
            }

            if ($this->search) {
                $search = $this->search;
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                      ->orWhere('first_name', 'like', "%{$search}%")
                      ->orWhere('gift', 'like', "%{$search}%")
                      ->orWhereHas('giftRequest.family', function ($q) use ($search) {
                          $q->where('last_name', 'like', "%{$search}%");
                      });
                });
            }

            if ($this->sortBy === 'family_name') {
                $query->join('gift_requests', 'children.gift_request_id', '=', 'gift_requests.id')
                      ->join('families', 'gift_requests.family_id', '=', 'families.id')
                      ->orderBy('families.last_name', $this->sortDirection)
                      ->select('children.*');
            } else {
                $query->orderBy($this->sortBy, $this->sortDirection);
            }

            $children = $query->paginate(100);
        }

        return view('livewire.admin.children-monitoring', [
            'children' => $children,
            'statuses' => [
                Child::STATUS_PENDING => 'À valider',
                Child::STATUS_VALIDATED => 'Validé',
                Child::STATUS_REJECTED => 'Refusé',
                Child::STATUS_REJECTED_FINAL => 'Refusé définitivement',
                Child::STATUS_PRINTED => 'Imprimé',
                Child::STATUS_RECEIVED => 'Reçu',
                Child::STATUS_GIVEN => 'Donné',
            ],
        ]);
    }
}

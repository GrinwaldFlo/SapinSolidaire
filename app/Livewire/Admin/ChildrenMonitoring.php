<?php

namespace App\Livewire\Admin;

use App\Livewire\Admin\Concerns\ShowsFamilyModal;
use App\Models\AdminActionLog;
use App\Models\Child;
use App\Models\Season;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class ChildrenMonitoring extends Component
{
    use ShowsFamilyModal, WithPagination;

    private const SORTABLE_COLUMNS = [
        'first_name',
        'gender',
        'birth_year',
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

    #[Locked]
    public ?string $editingGiftChildId = null;

    public string $giftDraft = '';

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

    public function editGift(string $childId): void
    {
        $this->ensureAdmin();

        $child = Child::find($childId);

        if (! $child) {
            return;
        }

        $this->resetErrorBag('giftDraft');
        $this->editingGiftChildId = $child->id;
        $this->giftDraft = $child->gift;
    }

    public function cancelGiftEdit(): void
    {
        $this->editingGiftChildId = null;
        $this->giftDraft = '';
        $this->resetErrorBag('giftDraft');
    }

    public function saveGift(): void
    {
        $this->ensureAdmin();

        $this->giftDraft = trim($this->giftDraft);

        $this->validate(
            ['giftDraft' => ['required', 'string', 'max:255']],
            [
                'giftDraft.required' => 'Le cadeau est obligatoire.',
                'giftDraft.max' => 'Le cadeau ne peut pas dépasser 255 caractères.',
            ]
        );

        $child = $this->editingGiftChildId
            ? Child::with('giftRequest.family')->find($this->editingGiftChildId)
            : null;

        if (! $child) {
            $this->cancelGiftEdit();

            return;
        }

        $previousGift = $child->gift;
        $newGift = $this->giftDraft;

        if ($newGift !== $previousGift) {
            $child->update(['gift' => $newGift]);

            $family = $child->giftRequest?->family;

            AdminActionLog::record(
                AdminActionLog::ACTION_GIFT_UPDATED,
                sprintf(
                    'Cadeau modifié pour %s%s%s : « %s » → « %s »',
                    $child->first_name,
                    $child->code ? ' ('.$child->code.')' : '',
                    $family ? ', famille '.$family->last_name : '',
                    $previousGift,
                    $newGift
                ),
                $family?->id,
                $child->gift_request_id,
                $child->id,
            );
        }

        $this->cancelGiftEdit();
    }

    /**
     * Send a refused child of the current season back to the validation queue.
     */
    public function resetRejectedChild(string $childId): void
    {
        $this->ensureAdmin();

        $activeSeasonId = Season::getActive()?->id;

        if (! $activeSeasonId) {
            return;
        }

        $child = DB::transaction(function () use ($childId, $activeSeasonId) {
            $child = Child::whereKey($childId)
                ->where('status', Child::STATUS_REJECTED)
                ->whereHas('giftRequest', fn ($q) => $q->where('season_id', $activeSeasonId))
                ->lockForUpdate()
                ->first();

            $child?->resetToPending();

            return $child;
        });

        if (! $child) {
            return;
        }

        $family = $child->giftRequest?->family;

        AdminActionLog::record(
            AdminActionLog::ACTION_CHILD_STATUS_RESET,
            sprintf(
                'Statut enfant remis à valider : %s%s',
                $child->first_name,
                $family ? ', famille '.$family->last_name : ''
            ),
            $family?->id,
            $child->gift_request_id,
            $child->id,
        );
    }

    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
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

        $statusLabels = ['' => 'Tous les statuts', ...Child::statusLabels()];

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

            $children = $query->paginate(200);
        }

        $isAdmin = (bool) auth()->user()?->isAdmin();

        return view('livewire.admin.children-monitoring', [
            'children' => $children,
            'canEditGift' => $isAdmin,
            'canResetRejected' => $isAdmin
                && $this->selectedSeasonId !== null
                && $this->selectedSeasonId === Season::getActive()?->id,
            'statuses' => Child::statusLabels(),
        ]);
    }
}

<?php

namespace App\Livewire\Admin;

use App\Models\Child;
use App\Models\Family;
use App\Models\Season;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Livewire\Component;

class GiftDelivery extends Component
{
    private const FAMILIES_PER_PAGE = 10;

    private const DELIVERY_ELIGIBLE_STATUSES = [
        Child::STATUS_VALIDATED,
        Child::STATUS_PRINTED,
        Child::STATUS_RECEIVED,
        Child::STATUS_GIVEN,
    ];

    private const LARGE_OBJECT_TERMS = [
        [
            'needle' => 'velo',
            'label' => 'Vélos',
        ],
    ];

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

    public function exportSetupListingPdf()
    {
        if (! $this->activeSeason) {
            return;
        }

        $children = Child::query()
            ->with('giftRequest')
            ->whereHas('giftRequest', function ($query) {
                $query->where('season_id', $this->activeSeason->id)
                    ->whereNotNull('family_number');
            })
            ->whereIn('status', self::DELIVERY_ELIGIBLE_STATUSES)
            ->orderBy('code')
            ->get();

        $families = $children
            ->groupBy(fn (Child $child) => (int) $child->giftRequest->family_number)
            ->sortKeys()
            ->map(function (Collection $familyChildren, int $familyCode) {
                $giftTexts = $familyChildren
                    ->pluck('gift')
                    ->filter()
                    ->map(fn (string $gift) => Str::lower(Str::ascii($gift)))
                    ->all();

                $largeObjects = collect(self::LARGE_OBJECT_TERMS)
                    ->map(function (array $definition) use ($giftTexts, $familyCode) {
                        $count = collect($giftTexts)
                            ->filter(fn (string $giftText) => Str::contains($giftText, $definition['needle']))
                            ->count();

                        if ($count === 0) {
                            return null;
                        }

                        return [
                            'label' => $definition['label'],
                            'count' => $count,
                            'display' => $definition['label'].' '.$familyCode.'/'.$count,
                        ];
                    })
                    ->filter()
                    ->values();

                return [
                    'family_code' => $familyCode,
                    'children_count' => $familyChildren->count(),
                    'large_objects' => $largeObjects,
                ];
            })
            ->values();

        $pages = $families->chunk(self::FAMILIES_PER_PAGE)->map(function (Collection $rows) {
            return [
                'first_code' => $rows->first()['family_code'],
                'last_code' => $rows->last()['family_code'],
                'rows' => $rows->values(),
            ];
        })->values();

        $pdf = Pdf::loadView('pdf.delivery-setup-listing', [
            'pages' => $pages,
        ]);

        $pdf->setPaper('a4');
        $pdf->setOption('margin-top', 10);
        $pdf->setOption('margin-bottom', 10);
        $pdf->setOption('margin-left', 10);
        $pdf->setOption('margin-right', 10);

        $filename = 'listing-mise-en-place-'.now()->format('Y-m-d-His').'.pdf';

        return response()->streamDownload(
            fn () => print($pdf->output()),
            $filename
        );
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

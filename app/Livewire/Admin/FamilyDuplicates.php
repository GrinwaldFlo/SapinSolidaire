<?php

namespace App\Livewire\Admin;

use App\Models\Family;
use App\Services\FamilyDuplicateService;
use Illuminate\Support\Collection;
use Livewire\Component;

class FamilyDuplicates extends Component
{
    public bool $loading = false;

    /** @var Collection|null */
    public ?Collection $pairs = null;

    // Merge modal state
    public bool $showMergeModal = false;
    public ?string $familyAId = null;
    public ?string $familyBId = null;
    public ?array $currentPair = null;

    /**
     * Which family to keep: 'A' or 'B'
     */
    public string $keepSide = 'A';

    /**
     * Fields where the user wants to take the value from the OTHER family (not the kept one).
     * e.g. ['email', 'phone'] means use familyB's email and phone when keeping A.
     */
    public array $overrideFields = [];

    public float $threshold = 40.0;

    public function mount(): void
    {
        // Do not auto-run to avoid slow page load
    }

    public function scan(): void
    {
        $this->loading = true;
        $service = app(FamilyDuplicateService::class);
        $rawPairs = $service->findDuplicates($this->threshold);

        // Store only lightweight data in Livewire state to stay under payload limits.
        // Full family details are loaded from DB when the merge modal opens.
        $this->pairs = $rawPairs->map(function ($pair) {
            return [
                'familyA'  => $this->serializeFamilyLight($pair['familyA']),
                'familyB'  => $this->serializeFamilyLight($pair['familyB']),
                'score'    => $pair['score'],
                'details'  => $pair['details'],
            ];
        });

        $this->loading = false;
    }

    public function openMerge(string $familyAId, string $familyBId): void
    {
        $this->familyAId = $familyAId;
        $this->familyBId = $familyBId;

        $familyA = Family::with('giftRequests.season')->findOrFail($familyAId);
        $familyB = Family::with('giftRequests.season')->findOrFail($familyBId);

        // Store only scalar fields — no nested requests/children — to stay under payload limits.
        $this->currentPair = [
            'familyA' => $this->serializeFamilyLight($familyA),
            'familyB' => $this->serializeFamilyLight($familyB),
        ];

        // Default: keep the family that participated in the oldest season (most history).
        $oldestA = $familyA->giftRequests->filter(fn ($r) => $r->season)->min(fn ($r) => $r->season->start_date);
        $oldestB = $familyB->giftRequests->filter(fn ($r) => $r->season)->min(fn ($r) => $r->season->start_date);

        if ($oldestA !== null && $oldestB !== null) {
            $this->keepSide = $oldestA <= $oldestB ? 'A' : 'B';
        } elseif ($oldestA !== null) {
            $this->keepSide = 'A';
        } elseif ($oldestB !== null) {
            $this->keepSide = 'B';
        } else {
            $this->keepSide = 'A';
        }

        $this->overrideFields = [];
        $this->showMergeModal = true;
    }

    public function closeMerge(): void
    {
        $this->showMergeModal = false;
        $this->currentPair = null;
        $this->familyAId = null;
        $this->familyBId = null;
    }

    public function confirmMerge(): void
    {
        $keepFamily   = $this->keepSide === 'A'
            ? Family::findOrFail($this->familyAId)
            : Family::findOrFail($this->familyBId);

        $removeFamily = $this->keepSide === 'A'
            ? Family::findOrFail($this->familyBId)
            : Family::findOrFail($this->familyAId);

        $service = app(FamilyDuplicateService::class);
        $service->merge($keepFamily, $removeFamily, $this->overrideFields);

        $this->closeMerge();

        // Remove the merged pair from results
        if ($this->pairs !== null) {
            $keepId   = $keepFamily->id;
            $removeId = $removeFamily->id;
            $this->pairs = $this->pairs->filter(function ($pair) use ($keepId, $removeId) {
                $ids = [$pair['familyA']['id'], $pair['familyB']['id']];

                return ! in_array($keepId, $ids) && ! in_array($removeId, $ids);
            })->values();
        }

        // Re-scan to reflect changes
        $this->scan();

        session()->flash('success', 'Les deux familles ont été fusionnées avec succès.');
    }

    public function dismissPair(string $familyAId, string $familyBId): void
    {
        if ($this->pairs === null) {
            return;
        }

        $this->pairs = $this->pairs->filter(function ($pair) use ($familyAId, $familyBId) {
            return ! (
                ($pair['familyA']['id'] === $familyAId && $pair['familyB']['id'] === $familyBId) ||
                ($pair['familyA']['id'] === $familyBId && $pair['familyB']['id'] === $familyAId)
            );
        })->values();
    }

    /** Minimal data stored in $pairs to keep Livewire payload small. */
    private function serializeFamilyLight(Family $family): array
    {
        // Load seasons if not already eager-loaded
        if (! $family->relationLoaded('giftRequests')) {
            $family->load('giftRequests.season');
        }

        $seasons = $family->giftRequests
            ->filter(fn ($r) => $r->season !== null)
            ->sortBy(fn ($r) => $r->season->start_date)
            ->map(fn ($r) => $r->season->name)
            ->unique()
            ->values()
            ->toArray();

        return [
            'id'          => $family->id,
            'first_name'  => $family->first_name,
            'last_name'   => $family->last_name,
            'email'       => $family->email,
            'phone'       => $family->formatted_phone,
            'street_name' => $family->street_name,
            'house_no'    => $family->house_no,
            'postal_code' => $family->postal_code,
            'city'        => $family->city,
            'seasons'     => $seasons,
        ];
    }

    /** Full data used only inside the merge modal (loaded on demand). */
    private function serializeFamilyFull(Family $family): array
    {
        $seasons = $family->giftRequests
            ->filter(fn ($r) => $r->season !== null)
            ->sortBy(fn ($r) => $r->season->start_date)
            ->map(fn ($r) => $r->season->name)
            ->unique()
            ->values()
            ->toArray();

        return [
            'id'          => $family->id,
            'email'       => $family->email,
            'first_name'  => $family->first_name,
            'last_name'   => $family->last_name,
            'phone'       => $family->formatted_phone,
            'street_name' => $family->street_name,
            'house_no'    => $family->house_no,
            'postal_code' => $family->postal_code,
            'city'        => $family->city,
            'seasons'     => $seasons,
            'requests'    => $family->giftRequests->sortBy(fn ($r) => $r->season?->start_date)->map(fn ($r) => [
                'id'         => $r->id,
                'season'     => $r->season?->name ?? '—',
                'start_date' => $r->season?->start_date,
                'status'     => $r->status,
                'children'   => $r->children->map(fn ($c) => [
                    'first_name' => $c->first_name,
                    'birth_year' => $c->birth_year,
                    'gender'     => $c->gender,
                ])->toArray(),
            ])->toArray(),
        ];
    }

    public function render()
    {
        // Load full family details from DB for the merge modal only when needed.
        // This data is passed directly to the view and never stored in public state.
        $modalPair = null;
        if ($this->showMergeModal && $this->familyAId && $this->familyBId) {
            $familyA = Family::with(['giftRequests.children', 'giftRequests.season'])->findOrFail($this->familyAId);
            $familyB = Family::with(['giftRequests.children', 'giftRequests.season'])->findOrFail($this->familyBId);
            $modalPair = [
                'familyA' => $this->serializeFamilyFull($familyA),
                'familyB' => $this->serializeFamilyFull($familyB),
            ];
        }

        return view('livewire.admin.family-duplicates', compact('modalPair'));
    }
}

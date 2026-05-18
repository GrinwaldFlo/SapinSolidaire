<?php

namespace App\Livewire\Admin;

use App\Models\Family;
use App\Services\FamilyDuplicateService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class FamilyDuplicates extends Component
{
    public bool $loading = false;

    /**
     * Keys of pairs the user dismissed: ["aId_bId", ...]
     * Small array of strings — the only pair-related data kept in Livewire state.
     */
    public array $dismissedKeys = [];

    // Merge modal state
    public bool $showMergeModal = false;
    public ?string $familyAId = null;
    public ?string $familyBId = null;
    public ?array $currentPair = null;

    /** Which family to keep: 'A' or 'B' */
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

    private function pairsCacheKey(): string
    {
        return 'family_pairs_' . $this->getId();
    }

    private function modalCacheKey(): string
    {
        return 'merge_modal_' . $this->getId();
    }

    public function scan(): void
    {
        $this->loading = true;
        $service = app(FamilyDuplicateService::class);
        $rawPairs = $service->findDuplicates($this->threshold);

        // Cache the full pairs collection server-side.
        // Livewire state only holds small dismissed-key bookkeeping.
        $pairs = $rawPairs->map(function ($pair) {
            return [
                'familyA' => $this->serializeFamilyLight($pair['familyA']),
                'familyB' => $this->serializeFamilyLight($pair['familyB']),
                'score'   => $pair['score'],
                'details' => $pair['details'],
            ];
        })->values()->all();

        Cache::put($this->pairsCacheKey(), $pairs, now()->addHours(2));
        $this->dismissedKeys = [];
        $this->loading = false;
    }

    public function openMerge(string $familyAId, string $familyBId): void
    {
        $this->familyAId = $familyAId;
        $this->familyBId = $familyBId;

        $familyA = Family::with(['giftRequests.children', 'giftRequests.season'])->findOrFail($familyAId);
        $familyB = Family::with(['giftRequests.children', 'giftRequests.season'])->findOrFail($familyBId);

        // Cache the full family data for the modal so render() does not re-query on every interaction.
        Cache::put($this->modalCacheKey(), [
            'familyA' => $this->serializeFamilyFull($familyA),
            'familyB' => $this->serializeFamilyFull($familyB),
        ], now()->addMinutes(30));

        // Store only scalar fields in Livewire state to stay under payload limits.
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
        Cache::forget($this->modalCacheKey());
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

        // Remove all pairs involving either merged family from the cache.
        $keepId   = $keepFamily->id;
        $removeId = $removeFamily->id;
        $pairs = Cache::get($this->pairsCacheKey(), []);
        $pairs = array_values(array_filter($pairs, function ($pair) use ($keepId, $removeId) {
            $ids = [$pair['familyA']['id'], $pair['familyB']['id']];

            return ! in_array($keepId, $ids) && ! in_array($removeId, $ids);
        }));
        Cache::put($this->pairsCacheKey(), $pairs, now()->addHours(2));

        $this->closeMerge();

        session()->flash('success', 'Les deux familles ont été fusionnées avec succès.');
    }

    public function dismissPair(string $familyAId, string $familyBId): void
    {
        $this->dismissedKeys[] = $familyAId . '_' . $familyBId;
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
        $cachedPairs = Cache::get($this->pairsCacheKey());

        if ($cachedPairs === null) {
            $pairs = null;
        } else {
            $dismissed = $this->dismissedKeys;
            $pairs = collect($cachedPairs)->filter(function ($pair) use ($dismissed) {
                $key = $pair['familyA']['id'] . '_' . $pair['familyB']['id'];

                return ! in_array($key, $dismissed);
            })->values();
        }

        // Retrieve cached full family data — loaded once in openMerge(), not re-queried on every render.
        $modalPair = $this->showMergeModal
            ? Cache::get($this->modalCacheKey())
            : null;

        return view('livewire.admin.family-duplicates', compact('pairs', 'modalPair'));
    }
}

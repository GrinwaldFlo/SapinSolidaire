<?php

namespace App\Livewire\Admin\Concerns;

use App\Models\Family;

trait ShowsFamilyModal
{
    public bool $showFamilyModal = false;
    public ?array $selectedFamily = null;

    public function showFamilyDetails(string $familyId): void
    {
        $family = Family::find($familyId);

        if (! $family) {
            return;
        }

        $seasonId = $this->selectedSeasonId ?? null;

        $children = collect();
        if ($seasonId) {
            $giftRequest = $family->giftRequests()->where('season_id', $seasonId)->first();
            if ($giftRequest) {
                $children = $giftRequest->children()->orderBy('first_name')->get()
                    ->map(fn ($child) => [
                        'first_name'   => $child->first_name,
                        'formatted_age' => $child->formatted_age,
                        'gender_label' => $child->gender !== 'unspecified' ? $child->gender_label : null,
                        'gift'         => $child->gift,
                    ]);
            }
        } else {
            $children = $family->giftRequests()
                ->with('children')
                ->get()
                ->flatMap(fn ($r) => $r->children)
                ->sortBy('first_name')
                ->map(fn ($child) => [
                    'first_name'   => $child->first_name,
                    'formatted_age' => $child->formatted_age,
                    'gender_label' => $child->gender !== 'unspecified' ? $child->gender_label : null,
                    'gift'         => $child->gift,
                ]);
        }

        $this->selectedFamily = [
            'last_name'      => $family->last_name,
            'first_name'     => $family->first_name,
            'email'          => $family->email,
            'phone'          => $family->phone,
            'formatted_phone' => $family->formatted_phone,
            'tel_phone'      => $family->tel_phone,
            'full_address'   => $family->full_address,
            'children'       => $children->values()->toArray(),
        ];

        $this->showFamilyModal = true;
    }

    public function closeFamilyModal(): void
    {
        $this->showFamilyModal = false;
        $this->selectedFamily = null;
    }
}

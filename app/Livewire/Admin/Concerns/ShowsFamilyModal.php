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

        $seasons = $family->giftRequests()
            ->with(['season', 'children'])
            ->get()
            ->sortByDesc(fn ($r) => $r->season?->start_date)
            ->map(fn ($giftRequest) => [
                'season_name' => $giftRequest->season?->name ?? '—',
                'children'    => collect($giftRequest->children)
                    ->sortByDesc('birth_year')
                    ->map(fn ($child) => [
                        'first_name'    => $child->first_name,
                        'formatted_age' => $child->formatted_age,
                        'gender_label'  => $child->gender !== 'unspecified' ? $child->gender_label : null,
                        'gift'          => $child->gift,
                    ])
                    ->values()
                    ->toArray(),
            ])
            ->values()
            ->toArray();

        $this->selectedFamily = [
            'last_name'       => $family->last_name,
            'first_name'      => $family->first_name,
            'email'           => $family->email,
            'phone'           => $family->phone,
            'formatted_phone' => $family->formatted_phone,
            'tel_phone'       => $family->tel_phone,
            'full_address'    => $family->full_address,
            'seasons'         => $seasons,
        ];

        $this->showFamilyModal = true;
    }

    public function closeFamilyModal(): void
    {
        $this->showFamilyModal = false;
        $this->selectedFamily = null;
    }
}

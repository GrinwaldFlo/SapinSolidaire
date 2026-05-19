<?php

namespace App\Livewire\Admin\Concerns;

use App\Models\Family;
use App\Services\FamilyDuplicateService;

trait ChecksDuplicateFamily
{
    public array $potentialDuplicates = [];

    protected function checkForDuplicates(): void
    {
        $this->potentialDuplicates = [];

        if (! $this->currentRequest) {
            return;
        }

        $currentFamily = $this->currentRequest->family;
        $service = new FamilyDuplicateService;
        $threshold = 52.0;

        $otherFamilies = Family::with(['giftRequests.children', 'giftRequests.season'])
            ->where('id', '!=', $currentFamily->id)
            ->get();

        $currentFamily->load(['giftRequests.children', 'giftRequests.season']);

        foreach ($otherFamilies as $other) {
            $result = $service->score($currentFamily, $other);
            if ($result['score'] >= $threshold) {
                $this->potentialDuplicates[] = [
                    'id'         => $other->id,
                    'first_name' => $other->first_name,
                    'last_name'  => $other->last_name,
                    'score'      => $result['score'],
                ];
            }
        }

        usort($this->potentialDuplicates, fn ($a, $b) => $b['score'] <=> $a['score']);
    }
}

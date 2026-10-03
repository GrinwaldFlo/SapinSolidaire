<?php

namespace App\Livewire\Family\Concerns;

use App\Models\PickupSlot;

trait HandlesGiftRequestEligibility
{
    public function acceptConsecutiveYears(): void
    {
        $this->consecutiveYearsAccepted = true;

        if ($this->consecutiveYearsAccepted && $this->pickupCommitmentAccepted && $this->cityAccepted) {
            $this->step = 2;
        }
    }

    public function acceptPickupCommitment(): void
    {
        $this->pickupCommitmentAccepted = true;

        if ($this->consecutiveYearsAccepted && $this->pickupCommitmentAccepted && $this->cityAccepted) {
            $this->step = 2;
        }
    }

    public function acceptCity(): void
    {
        if (! empty($this->allowedCities) && empty($this->selectedCity)) {
            $this->addError('selectedCity', 'Veuillez sélectionner une commune.');

            return;
        }

        $this->cityAccepted = true;

        if (! empty($this->selectedCity)) {
            $this->city = $this->selectedCity;
            $this->cityConfirmed = true;
        }

        if ($this->consecutiveYearsAccepted && $this->pickupCommitmentAccepted && $this->cityAccepted) {
            $this->step = 2;
        }
    }

    protected function buildPickupConditionDateText(): string
    {
        if (! $this->season) {
            return '';
        }

        $slots = PickupSlot::query()
            ->where('season_id', $this->season->id)
            ->orderBy('start_datetime')
            ->get();

        if ($slots->isEmpty()) {
            return '';
        }

        $firstStart = $slots->first()?->start_datetime;
        $lastEnd = $slots->last()?->end_datetime;

        if (! $firstStart || ! $lastEnd) {
            return '';
        }

        if ($firstStart->toDateString() === $lastEnd->toDateString()) {
            return 'le '.$firstStart->translatedFormat('l d F Y');
        }

        return sprintf(
            'entre le %s et le %s',
            $firstStart->translatedFormat('l d F Y'),
            $lastEnd->translatedFormat('l d F Y')
        );
    }

    public function requestCityChange(): void
    {
        $this->cityConfirmed = false;
        $this->showCityConfirmation = true;
    }

    public function confirmCity(): void
    {
        if (empty($this->city)) {
            $this->addError('city', 'Veuillez sélectionner une commune.');
            $this->showCityConfirmation = false;

            return;
        }

        if (! empty($this->allowedCities) && ! in_array($this->city, $this->allowedCities)) {
            $this->addError('city', 'Cette commune n\'est pas éligible.');
            $this->showCityConfirmation = false;

            return;
        }

        $this->cityConfirmed = true;
        $this->showCityConfirmation = false;
        $this->resetErrorBag('city');
        unset($this->fieldErrors['city']);
        $this->validateAddress();
    }

    public function cancelCityChange(): void
    {
        $this->showCityConfirmation = false;
    }
}

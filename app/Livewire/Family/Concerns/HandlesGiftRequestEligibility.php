<?php

namespace App\Livewire\Family\Concerns;

use App\Models\RegistrationFunnelCounter;

trait HandlesGiftRequestEligibility
{
    public function acceptConsecutiveYears(): void
    {
        $this->consecutiveYearsAccepted = true;
        $this->recordFunnelStep(RegistrationFunnelCounter::STEP_CONSECUTIVE_YEARS_ACCEPTED);

        if ($this->consecutiveYearsAccepted && $this->pickupCommitmentAccepted && $this->cityAccepted) {
            $this->step = 2;
        }
    }

    public function acceptPickupCommitment(): void
    {
        $this->pickupCommitmentAccepted = true;
        $this->recordFunnelStep(RegistrationFunnelCounter::STEP_PICKUP_ACCEPTED);

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
        $this->recordFunnelStep(RegistrationFunnelCounter::STEP_CITY_ACCEPTED);

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
        return $this->season?->pickupDateText() ?? '';
    }

    /**
     * Pickup commitment sentence. The address is shown on its own after
     * "à l'adresse suivante :", which reads correctly whatever the address
     * looks like (place name, street, multi-line).
     */
    public function pickupCommitmentSentence(): string
    {
        $sentence = trim('Je viendrai chercher le cadeau '.$this->pickupConditionDateText);

        if ($this->pickupConditionAddressText !== '') {
            return $sentence." à l'adresse suivante :";
        }

        return $sentence.'.';
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

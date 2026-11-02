<?php

namespace App\Livewire\Family\Concerns;

use App\Models\RegistrationFunnelCounter;

trait HandlesGiftRequestWizard
{
    public function nextFamilyFormStep(): void
    {
        $this->hasAttemptedSubmit = true;

        if ($this->isFamilyFormOnFamilyStep()) {
            $this->validateFamilyFields();
            $this->validatePhone();
            $this->validateAddress();
            $this->validateCity();

            if (isset($this->fieldErrors['firstName']) || isset($this->fieldErrors['lastName']) || isset($this->fieldErrors['phone']) || isset($this->fieldErrors['address']) || isset($this->fieldErrors['city'])) {
                return;
            }

            $this->recordFunnelStep(RegistrationFunnelCounter::STEP_FAMILY_INFO_COMPLETED);
        } elseif ($this->isFamilyFormOnProofStep()) {
            $this->validateProofOfHabitation();
            $this->validateProofFile();

            if (isset($this->fieldErrors['proofOfHabitation'])) {
                return;
            }

            $this->recordFunnelStep(RegistrationFunnelCounter::STEP_PROOF_COMPLETED);
        } elseif (($childIndex = $this->getFamilyFormCurrentChildIndex()) !== null) {
            $this->validateChild($childIndex, true);
            $this->validateChildrenDuplicates();

            $childFields = ['first_name', 'gender', 'birth_year', 'gift', 'shoe_size', 'height'];
            foreach ($childFields as $field) {
                if (isset($this->fieldErrors["children.{$childIndex}.{$field}"])) {
                    return;
                }
            }
        }

        if ($this->isFamilyFormOnAnonymousStep()) {
            return;
        }

        $maxStep = $this->getFamilyFormSummaryStep();
        $this->familyFormCurrentStep = min($this->familyFormCurrentStep + 1, $maxStep);

        if ($this->isFamilyFormOnSummaryStep()) {
            $this->recordFunnelStep(RegistrationFunnelCounter::STEP_CHILDREN_COMPLETED);
        }
    }

    public function previousFamilyFormStep(): void
    {
        $this->familyFormCurrentStep = max(1, $this->familyFormCurrentStep - 1);
    }

    public function setAnonymousChoice(bool $isAnonymous): void
    {
        $this->isAnonymous = $isAnonymous;

        $this->children = array_map(function (array $child): array {
            $child['anonymous'] = $this->isAnonymous;

            return $child;
        }, $this->children);

        $this->recordFunnelStep(RegistrationFunnelCounter::STEP_ANONYMOUS_CHOICE_MADE);

        $this->familyFormCurrentStep = min($this->familyFormCurrentStep + 1, $this->getFamilyFormSummaryStep());
    }

    public function goToFamilyInformationStep(): void
    {
        $this->familyFormCurrentStep = 1;
    }

    public function goToFamilyFormChildStep(int $index): void
    {
        if ($index < 0 || $index >= count($this->children)) {
            return;
        }

        $this->familyFormCurrentStep = $this->getFamilyFormChildrenStartStep() + $index;
    }

    public function addChildAndContinue(): void
    {
        $currentChildIndex = $this->getFamilyFormCurrentChildIndex();
        if ($currentChildIndex === null || ! $this->isChildValidForWizardStep($currentChildIndex)) {
            return;
        }

        $summaryStepBefore = $this->getFamilyFormSummaryStep();
        $this->addChild();

        $this->familyFormCurrentStep = $summaryStepBefore;
    }

    public function isFamilyFormOnFamilyStep(): bool
    {
        return $this->familyFormCurrentStep === 1;
    }

    public function isFamilyFormOnProofStep(): bool
    {
        return $this->proofOfHabitationEnabled && $this->familyFormCurrentStep === 2;
    }

    public function isFamilyFormOnAnonymousStep(): bool
    {
        return $this->familyFormCurrentStep === $this->getFamilyFormAnonymousStep();
    }

    public function isFamilyFormOnSummaryStep(): bool
    {
        return $this->familyFormCurrentStep === $this->getFamilyFormSummaryStep();
    }

    public function getFamilyFormCurrentChildIndex(): ?int
    {
        $childrenStart = $this->getFamilyFormChildrenStartStep();
        $summaryStep = $this->getFamilyFormSummaryStep();

        if ($this->familyFormCurrentStep < $childrenStart || $this->familyFormCurrentStep >= $summaryStep) {
            return null;
        }

        return $this->familyFormCurrentStep - $childrenStart;
    }

    public function getFamilyFormChildrenStartStep(): int
    {
        return $this->proofOfHabitationEnabled ? 4 : 3;
    }

    public function getFamilyFormAnonymousStep(): int
    {
        return $this->proofOfHabitationEnabled ? 3 : 2;
    }

    public function getFamilyFormSummaryStep(): int
    {
        return $this->getFamilyFormChildrenStartStep() + count($this->children);
    }

    public function getFamilyFormStepCount(): int
    {
        return 3 + ($this->proofOfHabitationEnabled ? 1 : 0) + count($this->children);
    }

    public function isChildComplete(int $index): bool
    {
        $child = $this->children[$index] ?? null;
        if (! $child) {
            return false;
        }

        if (empty($child['first_name']) || empty($child['gender']) || empty($child['birth_year']) || empty($child['gift'])) {
            return false;
        }

        if ($this->isShoeGift((string) ($child['gift'] ?? '')) && empty($child['shoe_size'])) {
            return false;
        }

        if ($this->isSizedGift((string) ($child['gift'] ?? '')) && empty($child['height'])) {
            return false;
        }

        return true;
    }

    public function isChildValidForWizardStep(int $index): bool
    {
        $child = $this->children[$index] ?? null;
        if (! $child) {
            return false;
        }

        if (! ($child['can_modify'] ?? true)) {
            return true;
        }

        if (! $this->isChildComplete($index)) {
            return false;
        }

        $birthYear = (int) ($child['birth_year'] ?? 0);
        $currentYear = (int) date('Y');
        $minBirthYear = $currentYear - $this->maxChildAge;

        if ($birthYear < $minBirthYear || $birthYear > $currentYear) {
            return false;
        }

        if ($this->isForbiddenGift((string) ($child['gift'] ?? ''))) {
            return false;
        }

        $targetKey = mb_strtolower(trim((string) ($child['first_name'] ?? ''))).'|'.$birthYear.'|'.((string) ($child['gender'] ?? ''));
        $occurrences = 0;
        foreach ($this->children as $existingChild) {
            $existingKey = mb_strtolower(trim((string) ($existingChild['first_name'] ?? ''))).'|'.((string) ($existingChild['birth_year'] ?? '')).'|'.((string) ($existingChild['gender'] ?? ''));
            if ($existingKey === $targetKey) {
                $occurrences++;
                if ($occurrences > 1) {
                    return false;
                }
            }
        }

        return true;
    }
}

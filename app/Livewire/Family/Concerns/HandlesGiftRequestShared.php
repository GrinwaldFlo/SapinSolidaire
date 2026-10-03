<?php

namespace App\Livewire\Family\Concerns;

use App\Models\Child;
use App\Models\Family;
use App\Models\FamilySubmissionLog;
use App\Models\GiftRequest;
use App\Services\AddressValidationService;
use App\Services\PhoneValidationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait HandlesGiftRequestShared
{
    protected function loadChildrenFromRequest(): void
    {
        $this->children = [];

        foreach ($this->giftRequest->children as $child) {
            $this->children[] = [
                'id' => $child->id,
                'first_name' => $child->first_name,
                'gender' => $child->gender,
                'anonymous' => $child->anonymous,
                'birth_year' => $child->birth_year,
                'height' => $child->height,
                'gift' => $child->gift,
                'shoe_size' => $child->shoe_size,
                'status' => $child->status,
                'can_modify' => $child->canModify(),
            ];
        }

        $this->childCount = count($this->children);
    }

    public function addChild(): void
    {
        $this->children[] = [
            'id' => null,
            'first_name' => '',
            'gender' => '',
            'anonymous' => $this->isAnonymous,
            'birth_year' => '',
            'height' => '',
            'gift' => '',
            'shoe_size' => '',
            'status' => 'pending',
            'can_modify' => true,
        ];

        $this->childCount = count($this->children);
    }

    public function removeChild(int $index): void
    {
        if (count($this->children) > 1) {
            unset($this->children[$index]);
            $this->children = array_values($this->children);
            $this->childCount = count($this->children);

            // Remove validation errors for the deleted child and re-index remaining ones
            $newFieldErrors = [];
            foreach ($this->fieldErrors as $key => $value) {
                if (preg_match('/^children\.(\d+)\.(.+)$/', $key, $matches)) {
                    $errorIndex = (int) $matches[1];
                    if ($errorIndex === $index) {
                        continue;
                    }
                    $newIndex = $errorIndex > $index ? $errorIndex - 1 : $errorIndex;
                    $newFieldErrors["children.{$newIndex}.{$matches[2]}"] = $value;
                } else {
                    $newFieldErrors[$key] = $value;
                }
            }
            $this->fieldErrors = $newFieldErrors;

            // Re-index touched fields the same way
            $newTouchedFields = [];
            foreach ($this->touchedFields as $field) {
                if (preg_match('/^children\.(\d+)\.(.+)$/', $field, $matches)) {
                    $fieldIndex = (int) $matches[1];
                    if ($fieldIndex === $index) {
                        continue;
                    }
                    $newIndex = $fieldIndex > $index ? $fieldIndex - 1 : $fieldIndex;
                    $newTouchedFields[] = "children.{$newIndex}.{$matches[2]}";
                } else {
                    $newTouchedFields[] = $field;
                }
            }
            $this->touchedFields = $newTouchedFields;

            $maxStep = $this->getFamilyFormSummaryStep();
            if ($this->familyFormCurrentStep > $maxStep) {
                $this->familyFormCurrentStep = $maxStep;
            }
        }
    }

    public function touchField(string $field): void
    {
        if (! in_array($field, $this->touchedFields)) {
            $this->touchedFields[] = $field;
        }
    }

    public function validateFamilyFields(): void
    {
        $allFields = ['firstName', 'lastName', 'city', 'phone'];

        foreach ($allFields as $field) {
            if ($this->hasAttemptedSubmit || ! empty($this->$field)) {
                $this->touchField($field);
            }
        }

        $rules = [
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
        ];

        $messages = [
            'firstName.required' => 'Le prénom est obligatoire.',
            'lastName.required' => 'Le nom est obligatoire.',
            'city.required' => 'La ville est obligatoire.',
            'phone.required' => 'Le numéro de téléphone est obligatoire.',
        ];

        try {
            $this->validate($rules, $messages);
            foreach (array_keys($rules) as $field) {
                unset($this->fieldErrors[$field]);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            $failingFields = $e->errors();
            foreach (array_keys($rules) as $field) {
                if (isset($failingFields[$field])) {
                    if ($this->hasAttemptedSubmit || ! empty($this->$field)) {
                        $this->fieldErrors[$field] = $failingFields[$field];
                    }
                } else {
                    unset($this->fieldErrors[$field]);
                }
            }
        }

        if ($this->hasAttemptedSubmit && (empty($this->streetName) || empty($this->houseNo) || empty($this->postalCode))) {
            $this->fieldErrors['address'] = ['Veuillez renseigner la rue, le numéro et le code postal.'];
        } elseif (! empty($this->streetName) && ! empty($this->houseNo) && ! empty($this->postalCode)) {
            if (isset($this->fieldErrors['address']) && str_contains($this->fieldErrors['address'][0] ?? '', 'renseigner')) {
                unset($this->fieldErrors['address']);
            }
        }
    }

    public function validatePhone(): void
    {
        $this->touchField('phone');

        if (empty($this->phone)) {
            return;
        }

        $phoneService = app(PhoneValidationService::class);
        if (! $phoneService->isValid($this->phone)) {
            $this->fieldErrors['phone'] = ['Le numéro de téléphone n\'est pas valide.'];
        } else {
            unset($this->fieldErrors['phone']);
        }
    }

    public function validateAddress(): void
    {
        $this->touchField('streetName');
        $this->touchField('houseNo');
        $this->touchField('postalCode');

        if (empty($this->streetName) || empty($this->houseNo) || empty($this->postalCode)) {
            if ($this->hasAttemptedSubmit) {
                $this->fieldErrors['address'] = ['Veuillez renseigner la rue, le numéro et le code postal.'];
            }

            return;
        }

        if (empty($this->city)) {
            return;
        }

        $addressService = app(AddressValidationService::class);
        $addressResult = $addressService->validate($this->streetName, $this->houseNo, $this->postalCode, $this->city);

        if (! $addressResult['Valide']) {
            $this->fieldErrors['address'] = [$addressResult['Message']];
        } else {
            unset($this->fieldErrors['address']);

            if (! empty($addressResult['FormatedAddress'])) {
                $formatted = $addressResult['FormatedAddress'];
                $this->streetName = $formatted['StreetName'] ?? $this->streetName;
                $this->houseNo = $formatted['HouseNo'] ?? $this->houseNo;
                $this->postalCode = $formatted['ZipCode'] ?? $this->postalCode;
            }
        }
    }

    public function validateCity(): void
    {
        $this->touchField('city');

        if (empty($this->city)) {
            return;
        }

        if (! empty($this->allowedCities)) {
            if (! in_array($this->city, $this->allowedCities)) {
                $this->fieldErrors['city'] = ['Cette commune n\'est pas éligible.'];
            } elseif (! $this->cityConfirmed) {
                $this->fieldErrors['city'] = ['Veuillez confirmer votre commune de résidence.'];
            } else {
                unset($this->fieldErrors['city']);
            }
        } else {
            unset($this->fieldErrors['city']);
        }
    }

    public function validateProofOfHabitation(): void
    {
        $this->touchField('proofOfHabitation');

        if (! $this->proofOfHabitationEnabled) {
            return;
        }

        if (! $this->existingProofPath && ! $this->proofOfHabitation) {
            if ($this->hasAttemptedSubmit) {
                $this->fieldErrors['proofOfHabitation'] = ['Le justificatif de domicile est obligatoire.'];
            }
        } else {
            unset($this->fieldErrors['proofOfHabitation']);
        }
    }

    public function updatedProofOfHabitation(): void
    {
        $this->validateProofFile();
    }

    public function validateProofFile(): void
    {
        if (! $this->proofOfHabitation) {
            return;
        }

        try {
            $this->validate([
                'proofOfHabitation' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
            ], [
                'proofOfHabitation.file' => 'Le justificatif doit être un fichier valide.',
                'proofOfHabitation.mimes' => 'Le fichier doit être une image (jpg, png, webp) ou un PDF.',
                'proofOfHabitation.max' => 'Le fichier ne doit pas dépasser 10 Mo.',
            ]);
            unset($this->fieldErrors['proofOfHabitation']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->fieldErrors['proofOfHabitation'] = $e->errors()['proofOfHabitation'] ?? ['Le fichier n\'est pas valide.'];
        }
    }

    public function validateChild(int $index, bool $forceRequired = false): void
    {
        $child = $this->children[$index] ?? null;
        if (! $child) {
            return;
        }

        $shouldShowRequiredErrors = $forceRequired || (! $this->familyFormMultiStepEnabled && $this->hasAttemptedSubmit);

        $fields = ['first_name', 'gender', 'birth_year', 'gift', 'shoe_size', 'height'];
        foreach ($fields as $field) {
            if ($shouldShowRequiredErrors || ! empty($child[$field])) {
                $this->touchField("children.{$index}.{$field}");
            }
        }

        if (empty($child['first_name'])) {
            if ($shouldShowRequiredErrors) {
                $this->fieldErrors["children.{$index}.first_name"] = ['Le prénom est obligatoire.'];
            }
        } else {
            unset($this->fieldErrors["children.{$index}.first_name"]);
        }

        if (empty($child['gender'])) {
            if ($shouldShowRequiredErrors) {
                $this->fieldErrors["children.{$index}.gender"] = ['Le genre est obligatoire.'];
            }
        } else {
            unset($this->fieldErrors["children.{$index}.gender"]);
        }

        $currentYear = (int) date('Y');
        $minBirthYear = $currentYear - $this->maxChildAge;

        if (empty($child['birth_year']) || ! is_numeric($child['birth_year'])) {
            if ($shouldShowRequiredErrors) {
                $this->fieldErrors["children.{$index}.birth_year"] = ['L\'année de naissance est obligatoire.'];
            }
        } elseif ((int) $child['birth_year'] < $minBirthYear) {
            $this->fieldErrors["children.{$index}.birth_year"] = ["L'enfant doit avoir au maximum {$this->maxChildAge} ans au 31.12.{$currentYear} (année de naissance minimum : {$minBirthYear})."];
        } elseif ((int) $child['birth_year'] > $currentYear) {
            $this->fieldErrors["children.{$index}.birth_year"] = ["L'année de naissance ne peut pas être dans le futur."];
        } else {
            unset($this->fieldErrors["children.{$index}.birth_year"]);
        }

        if (empty($child['gift'])) {
            if ($shouldShowRequiredErrors) {
                $this->fieldErrors["children.{$index}.gift"] = ['Le cadeau souhaité est obligatoire.'];
            }
        } elseif ($this->isForbiddenGift($child['gift'])) {
            $this->fieldErrors["children.{$index}.gift"] = ['Ce type de cadeau n\'est pas autorisé.'];
        } else {
            unset($this->fieldErrors["children.{$index}.gift"]);
        }

        if ($this->isShoeGift($child['gift'] ?? '') && empty($child['shoe_size'])) {
            if ($shouldShowRequiredErrors) {
                $this->fieldErrors["children.{$index}.shoe_size"] = ['La pointure est obligatoire pour les chaussures.'];
            }
        } else {
            unset($this->fieldErrors["children.{$index}.shoe_size"]);
        }

        if ($this->isSizedGift($child['gift'] ?? '') && empty($child['height'])) {
            if ($shouldShowRequiredErrors) {
                $this->fieldErrors["children.{$index}.height"] = ['La taille est obligatoire pour ce cadeau.'];
            }
        } else {
            unset($this->fieldErrors["children.{$index}.height"]);
        }

        $this->validateChildrenDuplicates();
    }

    public function validateChildrenDuplicates(): void
    {
        $seen = [];
        foreach ($this->children as $index => $child) {
            $key = mb_strtolower(trim($child['first_name'] ?? '')).'|'.($child['birth_year'] ?? '').'|'.($child['gender'] ?? '');
            if (isset($seen[$key])) {
                $this->fieldErrors["children.{$index}.first_name"] = ['Cet enfant semble être un doublon (même prénom, année de naissance et genre).'];
            } elseif (isset($this->fieldErrors["children.{$index}.first_name"])) {
                // Keep existing errors for this field
            } else {
                unset($this->fieldErrors["children.{$index}.first_name"]);
            }
            $seen[$key] = $index;
        }
    }

    public function submit(): void
    {
        if (! $this->canModify) {
            return;
        }

        $this->hasAttemptedSubmit = true;

        foreach (array_keys($this->children) as $index) {
            $this->validateChild($index, true);
        }
        $this->validateChildrenDuplicates();
        $this->validateFamilyFields();
        $this->validatePhone();
        $this->validateAddress();
        $this->validateCity();
        $this->validateProofOfHabitation();
        $this->validateProofFile();

        if ($this->fieldErrors !== []) {
            return;
        }

        $phoneService = app(PhoneValidationService::class);
        $formattedPhone = $phoneService->formatE164($this->phone);

        $proofPath = $this->giftRequest?->proof_of_habitation_path;
        $oldProofPath = null;
        if ($this->proofOfHabitation) {
            $oldProofPath = $proofPath;
            $proofPath = $this->proofOfHabitation->store('proof-of-habitation', 'local');
        }

        $submissionAction = FamilySubmissionLog::ACTION_CREATED;

        DB::transaction(function () use ($formattedPhone, $proofPath, &$submissionAction): void {
            $this->family = Family::updateOrCreate(
                ['email' => $this->email],
                [
                    'first_name' => $this->firstName,
                    'last_name' => $this->lastName,
                    'street_name' => $this->streetName,
                    'house_no' => $this->houseNo,
                    'postal_code' => $this->postalCode,
                    'city' => $this->city,
                    'phone' => $formattedPhone,
                ]
            );

            $this->giftRequest = GiftRequest::updateOrCreate(
                [
                    'family_id' => $this->family->id,
                    'season_id' => $this->season->id,
                ],
                [
                    'status' => GiftRequest::STATUS_PENDING,
                    'status_changed_at' => now(),
                    'proof_of_habitation_path' => $proofPath,
                ]
            );
            $submissionAction = $this->giftRequest->wasRecentlyCreated
                ? FamilySubmissionLog::ACTION_CREATED
                : FamilySubmissionLog::ACTION_UPDATED;

            $existingChildIds = $this->giftRequest->children->pluck('id')->toArray();
            $updatedChildIds = [];

            foreach ($this->children as $childData) {
                $childRecord = null;

                if (! empty($childData['id'])) {
                    $childRecord = Child::find($childData['id']);
                }

                if ($childRecord && $childRecord->canModify()) {
                    $childRecord->update([
                        'first_name' => $childData['first_name'],
                        'gender' => $childData['gender'] ?? '',
                        'anonymous' => $this->isAnonymous,
                        'birth_year' => $childData['birth_year'],
                        'height' => $childData['height'] ?: null,
                        'gift' => $childData['gift'],
                        'shoe_size' => $childData['shoe_size'] ?: null,
                        'status' => Child::STATUS_PENDING,
                        'status_changed_at' => now(),
                    ]);
                    $updatedChildIds[] = $childRecord->id;
                } elseif (empty($childData['id'])) {
                    $newChild = Child::create([
                        'gift_request_id' => $this->giftRequest->id,
                        'first_name' => $childData['first_name'],
                        'gender' => $childData['gender'] ?? '',
                        'anonymous' => $this->isAnonymous,
                        'birth_year' => $childData['birth_year'],
                        'height' => $childData['height'] ?: null,
                        'gift' => $childData['gift'],
                        'shoe_size' => $childData['shoe_size'] ?: null,
                    ]);
                    $updatedChildIds[] = $newChild->id;
                } else {
                    $updatedChildIds[] = $childData['id'];
                }
            }

            $childrenToDelete = array_diff($existingChildIds, $updatedChildIds);
            Child::whereIn('id', $childrenToDelete)
                ->whereIn('status', [Child::STATUS_PENDING, Child::STATUS_REJECTED, Child::STATUS_VALIDATED])
                ->delete();
        });

        FamilySubmissionLog::create([
            'email' => $this->email,
            'action_type' => $submissionAction,
            'family_id' => $this->family?->id,
            'gift_request_id' => $this->giftRequest?->id,
        ]);

        if ($oldProofPath && $oldProofPath !== $proofPath) {
            Storage::disk('local')->delete($oldProofPath);
        }

        $this->submitted = true;
    }

    protected function isForbiddenGift(string $gift): bool
    {
        if (empty($this->giftRestrictions)) {
            return false;
        }

        $giftLower = mb_strtolower($gift);

        foreach ($this->giftRestrictions as $keyword) {
            if (str_contains($giftLower, mb_strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }

    protected function isShoeGift(string $gift): bool
    {
        $shoeKeywords = $this->giftsWithShoeSize !== []
            ? $this->giftsWithShoeSize
            : ['chaussure', 'basket', 'botte', 'sandale', 'soulier', 'sneaker'];

        return $this->matchesGiftKeywordList($gift, $shoeKeywords);
    }

    protected function isSizedGift(string $gift): bool
    {
        return $this->matchesGiftKeywordList($gift, $this->giftsWithSize);
    }

    public function shouldShowShoeSizeField(int $index): bool
    {
        $gift = (string) ($this->children[$index]['gift'] ?? '');

        return $this->isShoeGift($gift);
    }

    public function shouldShowHeightField(int $index): bool
    {
        $gift = (string) ($this->children[$index]['gift'] ?? '');

        return $this->isSizedGift($gift);
    }

    /**
     * @param array<int, string> $keywords
     */
    protected function matchesGiftKeywordList(string $gift, array $keywords): bool
    {
        if ($gift === '' || $keywords === []) {
            return false;
        }

        $normalizedGift = $this->normalizeGiftKeywordValue($gift);

        foreach ($keywords as $keyword) {
            if (str_contains($normalizedGift, $this->normalizeGiftKeywordValue($keyword))) {
                return true;
            }
        }

        return false;
    }

    protected function normalizeGiftKeywordValue(string $value): string
    {
        return mb_strtolower(Str::ascii($value));
    }
}

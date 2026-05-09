<?php

namespace App\Livewire\Family;

use App\Models\Child;
use App\Models\EmailToken;
use App\Models\Family;
use App\Models\GiftRequest;
use App\Models\Season;
use App\Models\Setting;
use App\Services\AddressValidationService;
use App\Services\PhoneValidationService;
use App\Services\SeasonService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.family')]
class GiftRequestForm extends Component
{
    use WithFileUploads;

    // Token and email
    public string $token;
    public string $email = '';

    // States
    public int $step = 1; // 1: eligibility, 2: form
    public bool $tokenValid = false;
    public bool $consecutiveYearsAccepted = false;
    public bool $cityAccepted = false;
    public bool $cityConfirmed = false;
    public bool $showCityConfirmation = false;
    public bool $isModifying = false;
    public bool $canModify = true;
    public bool $submitted = false;

    // Family data
    public string $firstName = '';
    public string $lastName = '';
    public string $streetName = '';
    public string $houseNo = '';
    public string $postalCode = '';
    public string $city = '';
    public string $phone = '';
    public bool $isAnonymous = false;

    // Proof of habitation
    public $proofOfHabitation = null;
    public bool $proofOfHabitationEnabled = false;
    public ?string $existingProofPath = null;

    // Real-time validation tracking
    public array $touchedFields = [];
    public array $fieldErrors = [];
    public bool $hasAttemptedSubmit = false;

    // Children
    public array $children = [];
    public int $childCount = 1;

    // Season
    public ?Season $season = null;
    public ?Family $family = null;
    public ?GiftRequest $giftRequest = null;

    // Settings
    public int $maxConsecutiveYears = 3;
    public int $maxChildAge = 12;
    public array $allowedCities = [];
    public string $selectedCity = '';
    public array $giftSuggestions = [];
    public array $giftRestrictions = [];

    public function mount(string $token): void
    {
        try {
            $this->token = $token;

            // Validate token and retrieve email
            $emailToken = EmailToken::findValidToken($token);
            if (! $emailToken) {
                $this->tokenValid = false;

                return;
            }

            $this->email = $emailToken->email;

            $this->tokenValid = true;

            // Check active season
            $seasonService = app(SeasonService::class);
            $status = $seasonService->getCurrentStatus();

            if ($status['status'] !== 'active') {
                $this->tokenValid = false;

                return;
            }

            $this->season = $status['season'];

            // Load settings
            $this->maxConsecutiveYears = Setting::getMaxConsecutiveYears();
            $this->maxChildAge = Setting::getMaxChildAge();
            $this->allowedCities = Setting::getAllowedCities();
            $this->giftSuggestions = Setting::getGiftSuggestions();
            $this->giftRestrictions = Setting::getGiftRestrictions();
            $this->proofOfHabitationEnabled = Setting::isProofOfHabitationEnabled();

            // Check if family exists
            $this->family = Family::where('email', $this->email)->first();

            if ($this->family) {
                // Load family data
                $this->firstName = $this->family->first_name ?? '';
                $this->lastName = $this->family->last_name ?? '';
                $this->streetName = $this->family->street_name ?? '';
                $this->houseNo = $this->family->house_no ?? '';
                $this->postalCode = $this->family->postal_code ?? '';
                $this->city = $this->family->city ?? '';

                $rawPhone = $this->family->phone ?? '';
                $phoneService = app(PhoneValidationService::class);
                $this->phone = ($rawPhone && ($formatted = $phoneService->formatInternational($rawPhone)))
                    ? $formatted
                    : $rawPhone;

                // Check for existing request this season
                $this->giftRequest = $this->family->getRequestForSeason($this->season);

                if ($this->giftRequest) {
                    $this->isModifying = true;
                    $this->canModify = $this->season->canModify();
                    $this->existingProofPath = $this->giftRequest->proof_of_habitation_path;

                    // Load children for this request
                    $this->loadChildrenFromRequest();

                    if (!empty($this->children)) {
                        $this->isAnonymous = $this->children[0]['anonymous'] ?? false;
                    }

                    // Skip eligibility if already accepted
                    $this->step = 2;
                    $this->consecutiveYearsAccepted = true;
                    $this->cityAccepted = true;
                    $this->cityConfirmed = true;
                }
            }

            // Initialize one child if none exist
            if (empty($this->children)) {
                $this->addChild();
            }
        } catch (\Throwable $e) {
            // Log the error but don't throw it - let the component render with tokenValid=false
            \Illuminate\Support\Facades\Log::error('GiftRequestForm mount error: '.$e->getMessage(), [
                'token' => $token,
                'exception' => $e,
            ]);
            $this->tokenValid = false;
        }
    }

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

    public function acceptConsecutiveYears(): void
    {
        $this->consecutiveYearsAccepted = true;

        if ($this->consecutiveYearsAccepted && $this->cityAccepted) {
            $this->step = 2;
        }
    }

    public function acceptCity(): void
    {
        if (!empty($this->allowedCities) && empty($this->selectedCity)) {
            $this->addError('selectedCity', 'Veuillez sélectionner une commune.');
            return;
        }

        $this->cityAccepted = true;

        if (!empty($this->selectedCity)) {
            $this->city = $this->selectedCity;
            $this->cityConfirmed = true;
        }

        if ($this->consecutiveYearsAccepted && $this->cityAccepted) {
            $this->step = 2;
        }
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

        if (!empty($this->allowedCities) && !in_array($this->city, $this->allowedCities)) {
            $this->addError('city', 'Cette commune n\'est pas éligible.');
            $this->showCityConfirmation = false;
            return;
        }

        $this->cityConfirmed = true;
        $this->showCityConfirmation = false;
        $this->resetErrorBag('city');
    }

    public function cancelCityChange(): void
    {
        $this->showCityConfirmation = false;
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
                        continue; // Drop errors for removed child
                    }
                    // Re-index errors for children that shifted down
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
        }
    }

    // Real-time validation methods
    public function touchField(string $field): void
    {
        if (! in_array($field, $this->touchedFields)) {
            $this->touchedFields[] = $field;
        }
    }

    public function validateFamilyFields(): void
    {
        $allFields = ['firstName', 'lastName', 'city', 'phone'];

        // Only touch fields that already have a value, or touch all on submit
        foreach ($allFields as $field) {
            if ($this->hasAttemptedSubmit || !empty($this->$field)) {
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
                    // Only show required errors for empty fields after submit attempt
                    if ($this->hasAttemptedSubmit || !empty($this->$field)) {
                        $this->fieldErrors[$field] = $failingFields[$field];
                    }
                } else {
                    unset($this->fieldErrors[$field]);
                }
            }
        }

        // Address required fields (street, houseNo, postalCode) are validated together
        if ($this->hasAttemptedSubmit && (empty($this->streetName) || empty($this->houseNo) || empty($this->postalCode))) {
            $this->fieldErrors['address'] = ['Veuillez renseigner la rue, le numéro et le code postal.'];
        } elseif (!empty($this->streetName) && !empty($this->houseNo) && !empty($this->postalCode)) {
            // Only clear if there is no API-level address error already set
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

        // Required fields check (only shown after submit attempt)
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

            // Update address with formatted data
            if (! empty($addressResult['FormatedAddress'])) {
                $formatted = $addressResult['FormatedAddress'];
                $this->streetName = $formatted['StreetName'] ?? $this->streetName;
                $this->houseNo    = $formatted['HouseNo']    ?? $this->houseNo;
                $this->postalCode = $formatted['ZipCode']    ?? $this->postalCode;
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

    public function validateChild(int $index): void
    {
        $child = $this->children[$index] ?? null;
        if (! $child) {
            return;
        }

        $fields = ['first_name', 'gender', 'birth_year', 'gift', 'shoe_size'];
        foreach ($fields as $field) {
            if ($this->hasAttemptedSubmit || !empty($child[$field])) {
                $this->touchField("children.{$index}.{$field}");
            }
        }

        if (empty($child['first_name'])) {
            if ($this->hasAttemptedSubmit) {
                $this->fieldErrors["children.{$index}.first_name"] = ['Le prénom est obligatoire.'];
            }
        } else {
            unset($this->fieldErrors["children.{$index}.first_name"]);
        }

        if (empty($child['gender'])) {
            if ($this->hasAttemptedSubmit) {
                $this->fieldErrors["children.{$index}.gender"] = ['Le genre est obligatoire.'];
            }
        } else {
            unset($this->fieldErrors["children.{$index}.gender"]);
        }

        $currentYear = (int) date('Y');
        $minBirthYear = $currentYear - $this->maxChildAge;

        if (empty($child['birth_year']) || ! is_numeric($child['birth_year'])) {
            if ($this->hasAttemptedSubmit) {
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
            if ($this->hasAttemptedSubmit) {
                $this->fieldErrors["children.{$index}.gift"] = ['Le cadeau souhaité est obligatoire.'];
            }
        } elseif ($this->isForbiddenGift($child['gift'])) {
            $this->fieldErrors["children.{$index}.gift"] = ['Ce type de cadeau n\'est pas autorisé.'];
        } else {
            unset($this->fieldErrors["children.{$index}.gift"]);
        }

        // Check if shoes require shoe size
        if ($this->isShoeGift($child['gift'] ?? '') && empty($child['shoe_size'])) {
            if ($this->hasAttemptedSubmit) {
                $this->fieldErrors["children.{$index}.shoe_size"] = ['La pointure est obligatoire pour les chaussures.'];
            }
        } else {
            unset($this->fieldErrors["children.{$index}.shoe_size"]);
        }
    }

    public function validateChildrenDuplicates(): void
    {
        // Check for duplicate children (same first_name, birth_year, gender)
        $seen = [];
        foreach ($this->children as $index => $child) {
            $key = mb_strtolower(trim($child['first_name'] ?? '')) . '|' . ($child['birth_year'] ?? '') . '|' . ($child['gender'] ?? '');
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

        // Touch all fields to trigger validation
        foreach (array_keys($this->children) as $index) {
            $this->validateChild($index);
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

        // Format phone to E.164
        $phoneService = app(PhoneValidationService::class);
        $formattedPhone = $phoneService->formatE164($this->phone);

        // Store proof of habitation file before the transaction
        $proofPath = $this->giftRequest?->proof_of_habitation_path;
        $oldProofPath = null;
        if ($this->proofOfHabitation) {
            $oldProofPath = $proofPath;
            $proofPath = $this->proofOfHabitation->store('proof-of-habitation', 'local');
        }

        // Save data
        DB::transaction(function () use ($formattedPhone, $proofPath) {
            // Create or update family
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

            // Create or update gift request
            $wasModifying = $this->isModifying;

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

            // Get existing child IDs
            $existingChildIds = $this->giftRequest->children->pluck('id')->toArray();
            $updatedChildIds = [];

            // Update or create children
            foreach ($this->children as $childData) {
                $childRecord = null;

                if (! empty($childData['id'])) {
                    $childRecord = Child::find($childData['id']);
                }

                if ($childRecord && $childRecord->canModify()) {
                    // Update existing child
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
                    // Create new child
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
                    // Keep existing child that can't be modified
                    $updatedChildIds[] = $childData['id'];
                }
            }

            // Delete removed children (only if they can be modified)
            $childrenToDelete = array_diff($existingChildIds, $updatedChildIds);
            Child::whereIn('id', $childrenToDelete)
                ->whereIn('status', [Child::STATUS_PENDING, Child::STATUS_REJECTED, Child::STATUS_VALIDATED])
                ->delete();
        });

        // Delete the old proof file after successful transaction
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
        $shoeKeywords = ['chaussure', 'basket', 'botte', 'sandale', 'soulier', 'sneaker'];

        $giftLower = strtolower($gift);

        foreach ($shoeKeywords as $keyword) {
            if (str_contains($giftLower, $keyword)) {
                return true;
            }
        }

        return false;
    }

    public function render()
    {
        return view('livewire.family.gift-request-form');
    }
}

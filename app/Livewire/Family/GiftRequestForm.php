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
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.family')]
class GiftRequestForm extends Component
{
    // Token and email
    public string $token;
    public string $email = '';

    // States
    public int $step = 1; // 1: eligibility, 2: form
    public bool $tokenValid = false;
    public bool $consecutiveYearsAccepted = false;
    public bool $postalCodeAccepted = false;
    public bool $isModifying = false;
    public bool $canModify = true;
    public bool $submitted = false;

    // Family data
    public string $firstName = '';
    public string $lastName = '';
    public string $address = '';
    public string $postalCode = '';
    public string $city = '';
    public string $phone = '';

    // Children
    public array $children = [];
    public int $childCount = 1;

    // Season
    public ?Season $season = null;
    public ?Family $family = null;
    public ?GiftRequest $giftRequest = null;

    // Settings
    public int $maxConsecutiveYears = 3;
    public array $allowedPostalCodes = [];
    public array $giftSuggestions = [];

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
            $this->allowedPostalCodes = Setting::getAllowedPostalCodes();
            $this->giftSuggestions = Setting::getGiftSuggestions();

            // Check if family exists
            $this->family = Family::where('email', $this->email)->first();

            if ($this->family) {
                // Load family data
                $this->firstName = $this->family->first_name ?? '';
                $this->lastName = $this->family->last_name ?? '';
                $this->address = $this->family->address ?? '';
                $this->postalCode = $this->family->postal_code ?? '';
                $this->city = $this->family->city ?? '';
                $this->phone = $this->family->phone ?? '';

                // Check for existing request this season
                $this->giftRequest = $this->family->getRequestForSeason($this->season);

                if ($this->giftRequest) {
                    $this->isModifying = true;
                    $this->canModify = $this->season->canModify();

                    // Load children for this request
                    $this->loadChildrenFromRequest();

                    // Skip eligibility if already accepted
                    $this->step = 2;
                    $this->consecutiveYearsAccepted = true;
                    $this->postalCodeAccepted = true;
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

        if ($this->consecutiveYearsAccepted && $this->postalCodeAccepted) {
            $this->step = 2;
        }
    }

    public function acceptPostalCode(): void
    {
        $this->postalCodeAccepted = true;

        if ($this->consecutiveYearsAccepted && $this->postalCodeAccepted) {
            $this->step = 2;
        }
    }

    public function addChild(): void
    {
        $this->children[] = [
            'id' => null,
            'first_name' => '',
            'gender' => 'unspecified',
            'anonymous' => false,
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
        }
    }

    public function submit(): void
    {
        if (! $this->canModify) {
            return;
        }

        // Validate family data
        $rules = [
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'postalCode' => ['required', 'string', 'max:10'],
            'city' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
        ];

        $messages = [
            'firstName.required' => 'Le prénom est obligatoire.',
            'lastName.required' => 'Le nom est obligatoire.',
            'address.required' => 'L\'adresse est obligatoire.',
            'postalCode.required' => 'Le code postal est obligatoire.',
            'city.required' => 'La ville est obligatoire.',
            'phone.required' => 'Le numéro de téléphone est obligatoire.',
        ];

        $this->validate($rules, $messages);

        // Validate phone
        $phoneService = app(PhoneValidationService::class);
        if (! $phoneService->isValid($this->phone)) {
            $this->addError('phone', 'Le numéro de téléphone n\'est pas valide.');

            return;
        }

        // Format phone to E.164
        $formattedPhone = $phoneService->formatE164($this->phone);

        // Validate address (optional API)
        $addressService = app(AddressValidationService::class);
        $addressResult = $addressService->validate($this->address, $this->postalCode, $this->city);

        if (! $addressResult['valid']) {
            $this->addError('address', $addressResult['message']);

            return;
        }

        // Validate postal code
        if (! empty($this->allowedPostalCodes) && ! in_array($this->postalCode, $this->allowedPostalCodes)) {
            $this->addError('postalCode', 'Ce code postal n\'est pas éligible.');

            return;
        }

        // Validate children
        foreach ($this->children as $index => $child) {
            if (empty($child['first_name'])) {
                $this->addError("children.{$index}.first_name", 'Le prénom est obligatoire.');
            }
            if (empty($child['birth_year']) || ! is_numeric($child['birth_year'])) {
                $this->addError("children.{$index}.birth_year", 'L\'année de naissance est obligatoire.');
            }
            if (empty($child['gift'])) {
                $this->addError("children.{$index}.gift", 'Le cadeau souhaité est obligatoire.');
            }
            // Check if shoes require shoe size
            if ($this->isShoeGift($child['gift']) && empty($child['shoe_size'])) {
                $this->addError("children.{$index}.shoe_size", 'La pointure est obligatoire pour les chaussures.');
            }
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        // Save data
        DB::transaction(function () use ($formattedPhone) {
            // Create or update family
            $this->family = Family::updateOrCreate(
                ['email' => $this->email],
                [
                    'first_name' => $this->firstName,
                    'last_name' => $this->lastName,
                    'address' => $this->address,
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
                        'gender' => $childData['gender'] ?? 'unspecified',
                        'anonymous' => $childData['anonymous'] ?? false,
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
                        'gender' => $childData['gender'] ?? 'unspecified',
                        'anonymous' => $childData['anonymous'] ?? false,
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

        $this->submitted = true;
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

<?php

namespace App\Livewire\Family;

use App\Livewire\Family\Concerns\HandlesGiftRequestEligibility;
use App\Livewire\Family\Concerns\HandlesGiftRequestShared;
use App\Livewire\Family\Concerns\HandlesGiftRequestWizard;
use App\Models\EmailToken;
use App\Models\Family;
use App\Models\GiftRequest;
use App\Models\Season;
use App\Models\Setting;
use App\Services\PhoneValidationService;
use App\Services\SeasonService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.family')]
class GiftRequestForm extends Component
{
    use HandlesGiftRequestEligibility;
    use HandlesGiftRequestShared;
    use HandlesGiftRequestWizard;
    use WithFileUploads;

    // Token and email
    public string $token;
    public string $email = '';

    // States
    public int $step = 1; // 1: eligibility, 2: form
    public bool $tokenValid = false;
    public bool $consecutiveYearsAccepted = false;
    public bool $pickupCommitmentAccepted = false;
    public bool $cityAccepted = false;
    public bool $cityConfirmed = false;
    public bool $showCityConfirmation = false;
    public bool $isModifying = false;
    public bool $canModify = true;
    public bool $submitted = false;
    public bool $isPermanentlyRejected = false;
    public string $organizerEmail = '';
    public ?string $rejectionComment = null;

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
    public array $giftsWithShoeSize = [];
    public array $giftsWithSize = [];
    public string $pickupConditionDateText = '';
    public string $pickupConditionAddressText = '';
    public bool $familyFormMultiStepEnabled = false;
    public int $familyFormCurrentStep = 1;

    public function mount(string $token): void
    {
        try {
            $this->token = $token;

            $emailToken = EmailToken::findValidToken($token);
            if (! $emailToken) {
                $this->tokenValid = false;

                return;
            }

            $this->email = $emailToken->email;
            $this->tokenValid = true;

            $seasonService = app(SeasonService::class);
            $status = $seasonService->getCurrentStatus();

            if ($status['status'] !== 'active') {
                $this->tokenValid = false;

                return;
            }

            $this->season = $status['season'];
            $this->organizerEmail = $this->season->responsible_email ?? '';
            $this->pickupConditionAddressText = trim((string) ($this->season->pickup_address ?? ''));
            $this->pickupConditionDateText = $this->buildPickupConditionDateText();

            $this->maxConsecutiveYears = Setting::getMaxConsecutiveYears();
            $this->maxChildAge = Setting::getMaxChildAge();
            $this->allowedCities = Setting::getAllowedCities();
            $this->giftSuggestions = Setting::getGiftSuggestions();
            $this->giftRestrictions = Setting::getGiftRestrictions();
            $this->giftsWithShoeSize = Setting::getGiftsWithShoeSize();
            $this->giftsWithSize = Setting::getGiftsWithSize();
            $this->proofOfHabitationEnabled = Setting::isProofOfHabitationEnabled();
            $this->familyFormMultiStepEnabled = Setting::isFamilyFormMultiStepEnabled();

            $this->family = Family::where('email', $this->email)->first();

            if ($this->family) {
                $permanentlyRejected = GiftRequest::where('family_id', $this->family->id)
                    ->where('status', GiftRequest::STATUS_REJECTED_FINAL)
                    ->exists();

                if ($permanentlyRejected) {
                    $this->isPermanentlyRejected = true;
                    $rejectedRequest = GiftRequest::where('family_id', $this->family->id)
                        ->where('status', GiftRequest::STATUS_REJECTED_FINAL)
                        ->latest()
                        ->first();
                    $this->rejectionComment = $rejectedRequest?->rejection_comment;

                    return;
                }

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

                $this->giftRequest = $this->family->getRequestForSeason($this->season);

                if ($this->giftRequest) {
                    $this->isModifying = true;
                    $this->canModify = $this->season->canModify();
                    $this->existingProofPath = $this->giftRequest->proof_of_habitation_path;

                    $this->loadChildrenFromRequest();

                    if (! empty($this->children)) {
                        $this->isAnonymous = $this->children[0]['anonymous'] ?? false;
                    }

                    $this->step = 2;
                    $this->consecutiveYearsAccepted = true;
                    $this->pickupCommitmentAccepted = true;
                    $this->cityAccepted = true;
                    $this->cityConfirmed = true;
                }
            }

            if (empty($this->children)) {
                $this->addChild();
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('GiftRequestForm mount error: '.$e->getMessage(), [
                'token' => $token,
                'exception' => $e,
            ]);
            $this->tokenValid = false;
        }
    }

    #[Computed]
    public function years(): array
    {
        $currentYear = date('Y');

        return range($currentYear - $this->maxConsecutiveYears + 1, $currentYear - 1);
    }

    public function render()
    {
        return view('livewire.family.gift-request-form');
    }
}

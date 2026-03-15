<?php

namespace App\Livewire\Admin;

use App\Models\Child;
use App\Models\Setting;
use Livewire\Component;

class SettingsManagement extends Component
{
    public string $siteName = '';
    public string $allowedCities = '';
    public int $maxConsecutiveYears = 3;
    public string $giftSuggestions = '';
    public string $introductionText = '';
    public string $replyToEmail = '';
    public string $codePrefix = '';
    public int $codeFamilyPadding = 4;
    public bool $proofOfHabitationEnabled = false;
    public string $pdfStyle = 'label';

    public function mount(): void
    {
        $this->siteName = Setting::getSiteName();
        $this->allowedCities = Setting::getValue(Setting::ALLOWED_CITIES, '');
        $this->maxConsecutiveYears = Setting::getMaxConsecutiveYears();
        $this->giftSuggestions = Setting::getValue(Setting::GIFT_SUGGESTIONS, '');
        $this->introductionText = Setting::getIntroductionText();
        $this->replyToEmail = Setting::getReplyToEmail() ?? '';
        $this->codePrefix = Setting::getCodePrefix();
        $this->codeFamilyPadding = Setting::getCodeFamilyPadding();
        $this->proofOfHabitationEnabled = Setting::isProofOfHabitationEnabled();
        $this->pdfStyle = Setting::getPdfStyle();
    }

    public function save(): void
    {
        $this->validate([
            'siteName' => ['required', 'string', 'max:255'],
            'maxConsecutiveYears' => ['required', 'integer', 'min:1', 'max:10'],
            'replyToEmail' => ['nullable', 'email'],
            'codePrefix' => ['nullable', 'string', 'max:10'],
            'codeFamilyPadding' => ['required', 'integer', 'min:1', 'max:10'],
            'pdfStyle' => ['required', 'in:label,grid'],
        ]);

        Setting::setValue(Setting::SITE_NAME, $this->siteName);
        $cities = array_filter(array_map('trim', explode(',', $this->allowedCities)), fn ($c) => $c !== '');
        sort($cities, SORT_STRING | SORT_FLAG_CASE);
        Setting::setValue(Setting::ALLOWED_CITIES, implode(', ', $cities));
        Setting::setValue(Setting::MAX_CONSECUTIVE_YEARS, $this->maxConsecutiveYears);
        Setting::setValue(Setting::GIFT_SUGGESTIONS, $this->giftSuggestions);
        Setting::setValue(Setting::INTRODUCTION_TEXT, $this->introductionText);
        Setting::setValue(Setting::REPLY_TO_EMAIL, $this->replyToEmail);
        $oldPrefix = Setting::getCodePrefix();
        $oldPadding = Setting::getCodeFamilyPadding();

        Setting::setValue(Setting::CODE_PREFIX, $this->codePrefix);
        Setting::setValue(Setting::CODE_FAMILY_PADDING, $this->codeFamilyPadding);

        if ($oldPrefix !== $this->codePrefix || $oldPadding !== $this->codeFamilyPadding) {
            Child::regenerateAllCodes($this->codePrefix, $this->codeFamilyPadding);
        }

        Setting::setValue(Setting::PROOF_OF_HABITATION_ENABLED, $this->proofOfHabitationEnabled ? '1' : '0');
        Setting::setValue(Setting::PDF_STYLE, $this->pdfStyle);

        session()->flash('message', 'Paramètres enregistrés avec succès.');
    }

    public function render()
    {
        return view('livewire.admin.settings-management');
    }
}

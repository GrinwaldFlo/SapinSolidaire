<?php

namespace App\Livewire\Admin;

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

    public function mount(): void
    {
        $this->siteName = Setting::getSiteName();
        $this->allowedCities = Setting::getValue(Setting::ALLOWED_CITIES, '');
        $this->maxConsecutiveYears = Setting::getMaxConsecutiveYears();
        $this->giftSuggestions = Setting::getValue(Setting::GIFT_SUGGESTIONS, '');
        $this->introductionText = Setting::getIntroductionText();
        $this->replyToEmail = Setting::getReplyToEmail() ?? '';
        $this->codePrefix = Setting::getCodePrefix();
    }

    public function save(): void
    {
        $this->validate([
            'siteName' => ['required', 'string', 'max:255'],
            'maxConsecutiveYears' => ['required', 'integer', 'min:1', 'max:10'],
            'replyToEmail' => ['nullable', 'email'],
            'codePrefix' => ['nullable', 'string', 'max:10'],
        ]);

        Setting::setValue(Setting::SITE_NAME, $this->siteName);
        Setting::setValue(Setting::ALLOWED_CITIES, $this->allowedCities);
        Setting::setValue(Setting::MAX_CONSECUTIVE_YEARS, $this->maxConsecutiveYears);
        Setting::setValue(Setting::GIFT_SUGGESTIONS, $this->giftSuggestions);
        Setting::setValue(Setting::INTRODUCTION_TEXT, $this->introductionText);
        Setting::setValue(Setting::REPLY_TO_EMAIL, $this->replyToEmail);
        Setting::setValue(Setting::CODE_PREFIX, $this->codePrefix);

        session()->flash('message', 'Paramètres enregistrés avec succès.');
    }

    public function render()
    {
        return view('livewire.admin.settings-management');
    }
}

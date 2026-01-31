<?php

namespace App\Livewire\Admin;

use App\Models\Setting;
use Livewire\Component;

class SettingsManagement extends Component
{
    public string $siteName = '';
    public string $allowedPostalCodes = '';
    public int $maxConsecutiveYears = 3;
    public string $giftSuggestions = '';
    public string $introductionText = '';
    public string $replyToEmail = '';

    public function mount(): void
    {
        $this->siteName = Setting::getSiteName();
        $this->allowedPostalCodes = Setting::getValue(Setting::ALLOWED_POSTAL_CODES, '');
        $this->maxConsecutiveYears = Setting::getMaxConsecutiveYears();
        $this->giftSuggestions = Setting::getValue(Setting::GIFT_SUGGESTIONS, '');
        $this->introductionText = Setting::getIntroductionText();
        $this->replyToEmail = Setting::getReplyToEmail() ?? '';
    }

    public function save(): void
    {
        $this->validate([
            'siteName' => ['required', 'string', 'max:255'],
            'maxConsecutiveYears' => ['required', 'integer', 'min:1', 'max:10'],
            'replyToEmail' => ['nullable', 'email'],
        ]);

        Setting::setValue(Setting::SITE_NAME, $this->siteName);
        Setting::setValue(Setting::ALLOWED_POSTAL_CODES, $this->allowedPostalCodes);
        Setting::setValue(Setting::MAX_CONSECUTIVE_YEARS, $this->maxConsecutiveYears);
        Setting::setValue(Setting::GIFT_SUGGESTIONS, $this->giftSuggestions);
        Setting::setValue(Setting::INTRODUCTION_TEXT, $this->introductionText);
        Setting::setValue(Setting::REPLY_TO_EMAIL, $this->replyToEmail);

        session()->flash('message', 'Paramètres enregistrés avec succès.');
    }

    public function render()
    {
        return view('livewire.admin.settings-management');
    }
}

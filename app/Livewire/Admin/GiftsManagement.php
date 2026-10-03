<?php

namespace App\Livewire\Admin;

use App\Models\Setting;
use Livewire\Component;

class GiftsManagement extends Component
{
    public string $giftSuggestions = '';
    public string $giftRestrictions = '';
    public string $giftsWithShoeSize = '';
    public string $giftsWithSize = '';

    public function mount(): void
    {
        $this->giftSuggestions = Setting::getValue(Setting::GIFT_SUGGESTIONS, '');
        $this->giftRestrictions = Setting::getValue(Setting::GIFT_RESTRICTIONS, '');
        $this->giftsWithShoeSize = Setting::getValue(Setting::GIFTS_WITH_SHOE_SIZE, '');
        $this->giftsWithSize = Setting::getValue(Setting::GIFTS_WITH_SIZE, '');
    }

    public function save(): void
    {
        $this->validate([
            'giftSuggestions' => ['nullable', 'string'],
            'giftRestrictions' => ['nullable', 'string'],
            'giftsWithShoeSize' => ['nullable', 'string'],
            'giftsWithSize' => ['nullable', 'string'],
        ]);

        Setting::setValue(Setting::GIFT_SUGGESTIONS, $this->giftSuggestions);
        Setting::setValue(Setting::GIFT_RESTRICTIONS, $this->giftRestrictions);
        Setting::setValue(Setting::GIFTS_WITH_SHOE_SIZE, $this->giftsWithShoeSize);
        Setting::setValue(Setting::GIFTS_WITH_SIZE, $this->giftsWithSize);

        session()->flash('message', 'Paramètres cadeaux enregistrés avec succès.');

        $this->redirectRoute('admin.dashboard');
    }

    public function render()
    {
        return view('livewire.admin.gifts-management');
    }
}

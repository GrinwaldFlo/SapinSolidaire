<?php

namespace App\Livewire\Admin;

use App\Services\CustomText;
use Livewire\Component;

class TextsManagement extends Component
{
    /**
     * Custom values keyed by setting key (empty = default text).
     *
     * @var array<string, string>
     */
    public array $texts = [];

    public function mount(): void
    {
        foreach (CustomText::keys() as $key) {
            $this->texts[$key] = CustomText::stored($key);
        }
    }

    public function save(): void
    {
        $rules = [];
        foreach (CustomText::definitions() as $key => $definition) {
            $rules["texts.{$key}"] = ['nullable', 'string', $definition['multiline'] ? 'max:5000' : 'max:255'];
        }

        $this->validate($rules);

        foreach (CustomText::keys() as $key) {
            CustomText::set($key, $this->texts[$key] ?? '');
        }

        session()->flash('message', 'Textes enregistrés avec succès.');

        $this->redirectRoute('admin.texts-management');
    }

    /**
     * Fill the field with the default text, as a base for editing.
     */
    public function useDefault(string $key): void
    {
        if (array_key_exists($key, $this->texts)) {
            $this->texts[$key] = CustomText::default($key);
        }
    }

    /**
     * Clear a custom text so the default one is used again.
     */
    public function resetText(string $key): void
    {
        if (array_key_exists($key, $this->texts)) {
            $this->texts[$key] = '';
        }
    }

    public function render()
    {
        return view('livewire.admin.texts-management', [
            'groups' => CustomText::groups(),
            'placeholders' => array_map(
                static fn (string $key): array => CustomText::placeholders($key),
                array_combine(CustomText::keys(), CustomText::keys())
            ),
        ]);
    }
}

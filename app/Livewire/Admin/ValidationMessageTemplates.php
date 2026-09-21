<?php

namespace App\Livewire\Admin;

use App\Models\Setting;
use Livewire\Component;

class ValidationMessageTemplates extends Component
{
    /**
     * @var array<int, string>
     */
    public array $messages = [''];

    public function mount(): void
    {
        $storedMessages = Setting::getValidationCommentTemplates();
        $this->messages = ! empty($storedMessages) ? $storedMessages : [''];
    }

    public function addMessage(): void
    {
        $this->messages[] = '';
    }

    public function removeMessage(int $index): void
    {
        if (! array_key_exists($index, $this->messages)) {
            return;
        }

        unset($this->messages[$index]);
        $this->messages = array_values($this->messages);

        if (empty($this->messages)) {
            $this->messages = [''];
        }
    }

    public function save(): void
    {
        $this->validate([
            'messages' => ['array'],
            'messages.*' => ['nullable', 'string', 'max:1000'],
        ]);

        $cleanedMessages = array_values(array_unique(array_filter(array_map(
            static fn (string $message): string => trim($message),
            $this->messages
        ), static fn (string $message): bool => $message !== '')));

        Setting::setValidationCommentTemplates($cleanedMessages);

        $this->messages = ! empty($cleanedMessages) ? $cleanedMessages : [''];

        session()->flash('message', 'Messages prédéfinis enregistrés avec succès.');
    }

    public function render()
    {
        return view('livewire.admin.validation-message-templates');
    }
}

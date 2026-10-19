<?php

namespace App\Livewire\Admin\Concerns;

/**
 * Resolve predefined comment templates and append them to a free-text comment.
 * The using component must expose an indexed `$predefinedRejectionMessages` array.
 */
trait InsertsCommentTemplates
{
    protected function resolveTemplateMessage(string $messageKey): string
    {
        if ($messageKey === '' || ! ctype_digit($messageKey)) {
            return '';
        }

        $index = (int) $messageKey;

        if (! array_key_exists($index, $this->predefinedRejectionMessages)) {
            return '';
        }

        return trim((string) $this->predefinedRejectionMessages[$index]);
    }

    protected function appendTemplateMessage(string $existing, string $template): string
    {
        $existing = trim($existing);
        $template = trim($template);

        if ($template === '') {
            return $existing;
        }

        if ($existing === '') {
            return $template;
        }

        return $existing."\n\n".$template;
    }
}

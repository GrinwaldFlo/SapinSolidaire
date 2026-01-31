<?php

namespace App\Services;

use App\Models\Child;
use Illuminate\Support\Str;

class CodeGeneratorService
{
    /**
     * Generate a unique 4-letter uppercase code.
     */
    public function generate(): string
    {
        do {
            $code = $this->generateCode();
        } while ($this->codeExists($code));

        return $code;
    }

    /**
     * Generate a random 4-letter uppercase code.
     */
    protected function generateCode(): string
    {
        return Str::upper(Str::random(4));
    }

    /**
     * Check if a code already exists.
     */
    protected function codeExists(string $code): bool
    {
        return Child::where('code', $code)->exists();
    }
}

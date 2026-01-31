<?php

namespace App\Services;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class PhoneValidationService
{
    protected PhoneNumberUtil $phoneUtil;

    public function __construct()
    {
        $this->phoneUtil = PhoneNumberUtil::getInstance();
    }

    /**
     * Validate a Swiss phone number.
     */
    public function isValid(string $phone): bool
    {
        try {
            $phoneNumber = $this->phoneUtil->parse($phone, 'CH');

            return $this->phoneUtil->isValidNumber($phoneNumber);
        } catch (NumberParseException $e) {
            return false;
        }
    }

    /**
     * Format a phone number to E.164 format.
     */
    public function formatE164(string $phone): ?string
    {
        try {
            $phoneNumber = $this->phoneUtil->parse($phone, 'CH');

            if (! $this->phoneUtil->isValidNumber($phoneNumber)) {
                return null;
            }

            return $this->phoneUtil->format($phoneNumber, PhoneNumberFormat::E164);
        } catch (NumberParseException $e) {
            return null;
        }
    }

    /**
     * Format a phone number for display (national format).
     */
    public function formatNational(string $phone): ?string
    {
        try {
            $phoneNumber = $this->phoneUtil->parse($phone, 'CH');

            return $this->phoneUtil->format($phoneNumber, PhoneNumberFormat::NATIONAL);
        } catch (NumberParseException $e) {
            return null;
        }
    }
}

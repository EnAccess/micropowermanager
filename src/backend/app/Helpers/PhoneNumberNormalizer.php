<?php

namespace App\Helpers;

use libphonenumber\NumberParseException;
use Propaganistas\LaravelPhone\PhoneNumber;

class PhoneNumberNormalizer {
    /**
     * Normalize a phone number to E.164 format using libphonenumber.
     * Trims whitespace, prepends '+' if missing, then formats via PhoneNumber::formatE164().
     * Returns the original (trimmed, with '+') phone string if parsing fails.
     */
    public static function normalize(?string $phone): ?string {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $phone = trim($phone);
        $hasPlus = str_starts_with($phone, '+');

        $phone = $hasPlus ? $phone : '+'.$phone;

        try {
            $phoneNumber = new PhoneNumber($phone);

            return $phoneNumber->formatE164();
        } catch (NumberParseException) {
            return $phone;
        }
    }
}

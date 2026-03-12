<?php

namespace App\Helpers;

class PhoneNumberNormalizer {
    /**
     * Normalize a phone number to canonical format: +<digits only>.
     * Strips spaces, hyphens, parentheses, dots, and other non-digit characters.
     */
    public static function normalize(?string $phone): ?string {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $phone = trim($phone);
        $hasPlus = str_starts_with($phone, '+');
        $digits = preg_replace('/\D/', '', $phone);

        if ($digits === '' || $digits === null) {
            return null;
        }

        return $hasPlus ? '+'.$digits : '+'.$digits;
    }
}

<?php

namespace App\Helpers;

class TokenGenerator {
    /**
     * Generate a random 12-digit token.
     *
     * @return string
     */
    public static function generate() {
        $token = '';
        for ($i = 0; $i < 12; ++$i) {
            $token .= random_int(0, 9);
        }

        return $token;
    }
}

<?php

namespace App\Helpers;

class PasswordGenerator {
    public static function generatePassword($passwordLength = 5): int {
        return random_int(10 ** ($passwordLength - 1), (10 ** $passwordLength) - 1);
    }
}

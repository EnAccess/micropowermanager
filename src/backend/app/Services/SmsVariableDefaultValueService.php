<?php

namespace App\Services;

use App\Models\SmsVariableDefaultValue;

class SmsVariableDefaultValueService {
    public function __construct(private SmsVariableDefaultValue $smsVariableDefaultValue) {}

    public function getSmsVariableDefaultValues(): mixed {
        return $this->smsVariableDefaultValue->newQuery()->get();
    }
}

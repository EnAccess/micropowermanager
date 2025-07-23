<?php

namespace App\Services;

use App\Models\SmsVariableDefaultValue;

class SmsVariableDefaultValueService {
    private SmsVariableDefaultValue $smsVariableDefaultValue;

    public function __construct(SmsVariableDefaultValue $smsVariableDefaultValue) {
        $this->smsVariableDefaultValue = $smsVariableDefaultValue;
    }

    public function getSmsVariableDefaultValues(): mixed {
        return $this->smsVariableDefaultValue->newQuery()->get();
    }
}

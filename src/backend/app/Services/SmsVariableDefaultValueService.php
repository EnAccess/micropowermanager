<?php

namespace App\Services;

use App\Models\SmsVariableDefaultValue;

class SmsVariableDefaultValueService {
    private $smsVariableDefaultValue;

    public function __construct(SmsVariableDefaultValue $smsVariableDefaultValue) {
        $this->smsVariableDefaultValue = $smsVariableDefaultValue;
    }

    public function getSmsVariableDefaultValues() {
        return $this->smsVariableDefaultValue->newQuery()->get();
    }
}

<?php

namespace Inensus\SparkMeter\Services;

use Inensus\SparkMeter\Models\SmSmsVariableDefaultValue;

class SmSmsVariableDefaultValueService {
    private $smsVariableDefaultValue;

    public function __construct(SmSmsVariableDefaultValue $smsVariableDefaultValue) {
        $this->smsVariableDefaultValue = $smsVariableDefaultValue;
    }

    public function getSmsVariableDefaultValues() {
        return $this->smsVariableDefaultValue->newQuery()->get();
    }

    public function createSmsVariableDefaultValues() {
        $smsVariableDefaultValues = [
            [
                'variable' => 'name',
                'value' => 'Herbert',
            ],
            [
                'variable' => 'surname',
                'value' => 'Kale',
            ],
            [
                'variable' => 'low_balance_limit',
                'value' => '1000',
            ],
            [
                'variable' => 'credit_balance',
                'value' => '900',
            ],
            [
                'variable' => 'meter_serial',
                'value' => 'SM-12321-232-1',
            ],
        ];
        collect($smsVariableDefaultValues)->each(function ($variable) {
            $this->smsVariableDefaultValue->newQuery()->firstOrCreate(
                ['variable' => $variable['variable']],
                $variable
            );
        });
    }
}

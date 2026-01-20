<?php

namespace App\Plugins\SparkMeter\Services;

use App\Plugins\SparkMeter\Models\SmSmsVariableDefaultValue;
use Illuminate\Database\Eloquent\Collection;

class SmSmsVariableDefaultValueService {
    public function __construct(
        private SmSmsVariableDefaultValue $smsVariableDefaultValue,
    ) {}

    /**
     * @return Collection<int, SmSmsVariableDefaultValue>
     */
    public function getSmsVariableDefaultValues(): Collection {
        return $this->smsVariableDefaultValue->newQuery()->get();
    }

    public function createSmsVariableDefaultValues(): void {
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
        collect($smsVariableDefaultValues)->each(function (array $variable) {
            $this->smsVariableDefaultValue->newQuery()->firstOrCreate(
                ['variable' => $variable['variable']],
                $variable
            );
        });
    }
}

<?php

namespace Inensus\SteamaMeter\Services;

use Inensus\SteamaMeter\Models\SteamaSmsVariableDefaultValue;

class SteamaSmsVariableDefaultValueService {
    private SteamaSmsVariableDefaultValue $smsVariableDefaultValue;

    public function __construct(SteamaSmsVariableDefaultValue $smsVariableDefaultValue) {
        $this->smsVariableDefaultValue = $smsVariableDefaultValue;
    }

    public function getSmsVariableDefaultValues() {
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
                'variable' => 'low_balance_warning',
                'value' => '1000',
            ],
            [
                'variable' => 'account_balance',
                'value' => '900',
            ],
        ];
        collect($smsVariableDefaultValues)->each(function (array $variable) {
            $this->smsVariableDefaultValue->newQuery()
                ->firstOrCreate(
                    ['variable' => $variable['variable']],
                    $variable
                );
        });
    }
}

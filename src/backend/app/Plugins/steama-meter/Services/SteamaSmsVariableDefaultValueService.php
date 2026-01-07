<?php

namespace Inensus\SteamaMeter\Services;

use Illuminate\Database\Eloquent\Collection;
use Inensus\SteamaMeter\Models\SteamaSmsVariableDefaultValue;

class SteamaSmsVariableDefaultValueService {
    public function __construct(
        private SteamaSmsVariableDefaultValue $smsVariableDefaultValue,
    ) {}

    /**
     * @return Collection<int, SteamaSmsVariableDefaultValue>
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

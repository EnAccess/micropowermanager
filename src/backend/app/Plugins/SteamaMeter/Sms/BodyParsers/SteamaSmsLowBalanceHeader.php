<?php

namespace App\Plugins\SteamaMeter\Sms\BodyParsers;

use App\Plugins\SteamaMeter\Models\SteamaCustomer;
use App\Sms\BodyParsers\SmsBodyParser;

class SteamaSmsLowBalanceHeader extends SmsBodyParser {
    protected $variables = ['name', 'surname'];

    public function __construct(protected SteamaCustomer $steamaCustomer) {}

    protected function getVariableValue(string $variable): mixed {
        return match ($variable) {
            'name' => $this->steamaCustomer->mpmPerson->name,
            'surname' => $this->steamaCustomer->mpmPerson->surname,
            default => $variable,
        };
    }
}

<?php

namespace Inensus\SteamaMeter\Sms\BodyParsers;

use App\Sms\BodyParsers\SmsBodyParser;
use Inensus\SteamaMeter\Models\SteamaCustomer;

class SteamaSmsBalanceFeedbackHeader extends SmsBodyParser {
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

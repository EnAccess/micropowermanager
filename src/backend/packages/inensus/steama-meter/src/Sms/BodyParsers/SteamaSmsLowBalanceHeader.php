<?php

namespace Inensus\SteamaMeter\Sms\BodyParsers;

use App\Sms\BodyParsers\SmsBodyParser;
use Inensus\SteamaMeter\Models\SteamaCustomer;

class SteamaSmsLowBalanceHeader extends SmsBodyParser {
    protected $variables = ['name', 'surname'];
    protected $steamaCustomer;

    public function __construct(SteamaCustomer $steamaCustomer) {
        $this->steamaCustomer = $steamaCustomer;
    }

    protected function getVariableValue($variable) {
        switch ($variable) {
            case 'name':
                $variable = $this->steamaCustomer->mpmPerson->name;
                break;
            case 'surname':
                $variable = $this->steamaCustomer->mpmPerson->surname;
                break;
        }

        return $variable;
    }
}

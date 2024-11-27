<?php

namespace Inensus\SteamaMeter\Sms\BodyParsers;

use App\Sms\BodyParsers\SmsBodyParser;
use Inensus\SteamaMeter\Models\SteamaCustomer;

class SteamaSmsLowBalanceBody extends SmsBodyParser {
    protected $variables = ['low_balance_warning', 'account_balance'];
    protected $steamaCustomer;

    public function __construct(SteamaCustomer $steamaCustomer) {
        $this->steamaCustomer = $steamaCustomer;
    }

    protected function getVariableValue($variable) {
        switch ($variable) {
            case 'low_balance_warning':
                $variable = $this->steamaCustomer->low_balance_warning;
                break;
            case 'account_balance':
                $variable = $this->steamaCustomer->account_balance;
                break;
        }

        return $variable;
    }
}

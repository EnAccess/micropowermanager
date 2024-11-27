<?php

namespace Inensus\SteamaMeter\Sms\BodyParsers;

use App\Sms\BodyParsers\SmsBodyParser;
use Inensus\SteamaMeter\Models\SteamaCustomer;

class SteamaSmsBalanceFeedbackBody extends SmsBodyParser {
    protected $variables = ['account_balance'];
    protected $steamaCustomer;

    public function __construct(SteamaCustomer $steamaCustomer) {
        $this->steamaCustomer = $steamaCustomer;
    }

    protected function getVariableValue($variable) {
        switch ($variable) {
            case 'account_balance':
                $variable = $this->steamaCustomer->account_balance;
                break;
        }

        return $variable;
    }
}

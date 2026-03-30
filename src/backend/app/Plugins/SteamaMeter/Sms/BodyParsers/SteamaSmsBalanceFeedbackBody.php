<?php

namespace App\Plugins\SteamaMeter\Sms\BodyParsers;

use App\Plugins\SteamaMeter\Models\SteamaCustomer;
use App\Sms\BodyParsers\SmsBodyParser;

class SteamaSmsBalanceFeedbackBody extends SmsBodyParser {
    protected $variables = ['account_balance'];

    public function __construct(protected SteamaCustomer $steamaCustomer) {}

    protected function getVariableValue(string $variable): mixed {
        if ($variable === 'account_balance') {
            $variable = $this->steamaCustomer->account_balance;
        }

        return $variable;
    }
}

<?php

namespace Inensus\SteamaMeter\Sms\BodyParsers;

use App\Sms\BodyParsers\SmsBodyParser;
use Inensus\SteamaMeter\Models\SteamaCustomer;

class SteamaSmsBalanceFeedbackBody extends SmsBodyParser {
    protected $variables = ['account_balance'];
    protected SteamaCustomer $steamaCustomer;

    public function __construct(SteamaCustomer $steamaCustomer) {
        $this->steamaCustomer = $steamaCustomer;
    }

    protected function getVariableValue(string $variable): mixed {
        if ($variable === 'account_balance') {
            $variable = $this->steamaCustomer->account_balance;
        }

        return $variable;
    }
}

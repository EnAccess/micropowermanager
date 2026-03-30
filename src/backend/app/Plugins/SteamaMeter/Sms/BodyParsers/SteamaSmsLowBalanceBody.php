<?php

namespace App\Plugins\SteamaMeter\Sms\BodyParsers;

use App\Plugins\SteamaMeter\Models\SteamaCustomer;
use App\Sms\BodyParsers\SmsBodyParser;

class SteamaSmsLowBalanceBody extends SmsBodyParser {
    protected $variables = ['low_balance_warning', 'account_balance'];

    public function __construct(protected SteamaCustomer $steamaCustomer) {}

    protected function getVariableValue(string $variable): mixed {
        return match ($variable) {
            'low_balance_warning' => $this->steamaCustomer->low_balance_warning,
            'account_balance' => $this->steamaCustomer->account_balance,
            default => $variable,
        };
    }
}

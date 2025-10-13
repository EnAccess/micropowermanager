<?php

namespace Inensus\SteamaMeter\Sms\BodyParsers;

use App\Sms\BodyParsers\SmsBodyParser;
use Inensus\SteamaMeter\Models\SteamaCustomer;

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

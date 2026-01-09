<?php

namespace Inensus\SparkMeter\Sms\BodyParsers;

use App\Sms\BodyParsers\SmsBodyParser;
use Inensus\SparkMeter\Models\SmCustomer;

class SparkSmsLowBalanceBody extends SmsBodyParser {
    protected $variables = ['low_balance_limit', 'credit_balance'];

    public function __construct(protected SmCustomer $sparkCustomer) {}

    protected function getVariableValue(string $variable): mixed {
        return match ($variable) {
            'low_balance_limit' => $this->sparkCustomer->low_balance_limit,
            'credit_balance' => $this->sparkCustomer->credit_balance,
            default => $variable,
        };
    }
}

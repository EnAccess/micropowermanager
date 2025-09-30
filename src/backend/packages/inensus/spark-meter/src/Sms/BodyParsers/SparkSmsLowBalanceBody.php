<?php

namespace Inensus\SparkMeter\Sms\BodyParsers;

use App\Sms\BodyParsers\SmsBodyParser;
use Inensus\SparkMeter\Models\SmCustomer;

class SparkSmsLowBalanceBody extends SmsBodyParser {
    protected $variables = ['low_balance_limit', 'credit_balance'];
    protected SmCustomer $sparkCustomer;

    public function __construct(SmCustomer $sparkCustomer) {
        $this->sparkCustomer = $sparkCustomer;
    }

    protected function getVariableValue(string $variable): mixed {
        switch ($variable) {
            case 'low_balance_limit':
                $variable = $this->sparkCustomer->low_balance_limit;
                break;
            case 'credit_balance':
                $variable = $this->sparkCustomer->credit_balance;
                break;
        }

        return $variable;
    }
}

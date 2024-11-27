<?php

namespace Inensus\SparkMeter\Sms\BodyParsers;

use App\Sms\BodyParsers\SmsBodyParser;
use Inensus\SparkMeter\Models\SmCustomer;

class SparkSmsLowBalanceBody extends SmsBodyParser {
    protected $variables = ['low_balance_limit', 'credit_balance'];
    protected $sparkCustomer;

    public function __construct(SmCustomer $sparkCustomer) {
        $this->sparkCustomer = $sparkCustomer;
    }

    protected function getVariableValue($variable) {
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

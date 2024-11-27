<?php

namespace Inensus\SparkMeter\Sms\BodyParsers;

use App\Sms\BodyParsers\SmsBodyParser;
use Inensus\SparkMeter\Models\SmCustomer;

class SparkSmsBalanceFeedbackBody extends SmsBodyParser {
    protected $variables = ['credit_balance'];
    protected $sparkCustomer;

    public function __construct(SmCustomer $sparkCustomer) {
        $this->sparkCustomer = $sparkCustomer;
    }

    protected function getVariableValue($variable) {
        switch ($variable) {
            case 'credit_balance':
                $variable = $this->sparkCustomer->credit_balance;
                break;
        }

        return $variable;
    }
}

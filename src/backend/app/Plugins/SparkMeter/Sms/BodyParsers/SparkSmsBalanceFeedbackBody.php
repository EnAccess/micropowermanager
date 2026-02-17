<?php

namespace App\Plugins\SparkMeter\Sms\BodyParsers;

use App\Plugins\SparkMeter\Models\SmCustomer;
use App\Sms\BodyParsers\SmsBodyParser;

class SparkSmsBalanceFeedbackBody extends SmsBodyParser {
    protected $variables = ['credit_balance'];

    public function __construct(protected SmCustomer $sparkCustomer) {}

    protected function getVariableValue(string $variable): mixed {
        if ($variable === 'credit_balance') {
            $variable = $this->sparkCustomer->credit_balance;
        }

        return $variable;
    }
}

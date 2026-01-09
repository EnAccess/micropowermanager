<?php

namespace Inensus\SparkMeter\Sms\BodyParsers;

use App\Sms\BodyParsers\SmsBodyParser;
use Inensus\SparkMeter\Models\SmCustomer;

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

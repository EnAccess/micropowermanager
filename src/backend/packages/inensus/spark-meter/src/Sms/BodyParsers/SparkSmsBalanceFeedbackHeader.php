<?php

namespace Inensus\SparkMeter\Sms\BodyParsers;

use App\Sms\BodyParsers\SmsBodyParser;
use Inensus\SparkMeter\Models\SmCustomer;

class SparkSmsBalanceFeedbackHeader extends SmsBodyParser {
    protected $variables = ['name', 'surname'];
    protected $sparkCustomer;

    public function __construct(SmCustomer $sparkCustomer) {
        $this->sparkCustomer = $sparkCustomer;
    }

    protected function getVariableValue($variable) {
        switch ($variable) {
            case 'name':
                $variable = $this->sparkCustomer->mpmPerson->name;
                break;
            case 'surname':
                $variable = $this->sparkCustomer->mpmPerson->surname;
                break;
        }

        return $variable;
    }
}

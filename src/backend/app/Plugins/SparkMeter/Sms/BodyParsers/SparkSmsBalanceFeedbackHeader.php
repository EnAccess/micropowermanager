<?php

namespace App\Plugins\SparkMeter\Sms\BodyParsers;

use App\Plugins\SparkMeter\Models\SmCustomer;
use App\Sms\BodyParsers\SmsBodyParser;

class SparkSmsBalanceFeedbackHeader extends SmsBodyParser {
    protected $variables = ['name', 'surname'];

    public function __construct(protected SmCustomer $sparkCustomer) {}

    protected function getVariableValue(string $variable): mixed {
        return match ($variable) {
            'name' => $this->sparkCustomer->mpmPerson->name,
            'surname' => $this->sparkCustomer->mpmPerson->surname,
            default => $variable,
        };
    }
}

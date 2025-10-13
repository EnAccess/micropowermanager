<?php

namespace Inensus\SparkMeter\Sms\BodyParsers;

use App\Sms\BodyParsers\SmsBodyParser;
use Inensus\SparkMeter\Models\SmCustomer;

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

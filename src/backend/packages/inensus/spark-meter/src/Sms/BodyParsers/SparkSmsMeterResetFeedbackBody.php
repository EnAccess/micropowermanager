<?php

namespace Inensus\SparkMeter\Sms\BodyParsers;

use App\Sms\BodyParsers\SmsBodyParser;

class SparkSmsMeterResetFeedbackBody extends SmsBodyParser {
    protected $variables = ['meter_serial'];

    public function __construct(protected $meter) {}

    protected function getVariableValue(string $variable): mixed {
        if ($variable === 'meter_serial') {
            $variable = $this->meter->serial_number;
        }

        return $variable;
    }
}

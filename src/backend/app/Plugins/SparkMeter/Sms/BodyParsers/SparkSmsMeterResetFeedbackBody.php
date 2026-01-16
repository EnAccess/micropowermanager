<?php

namespace App\Plugins\SparkMeter\Sms\BodyParsers;

use App\Models\Meter\Meter;
use App\Sms\BodyParsers\SmsBodyParser;

class SparkSmsMeterResetFeedbackBody extends SmsBodyParser {
    protected $variables = ['meter_serial'];

    public function __construct(
        protected Meter $meter,
    ) {}

    protected function getVariableValue(string $variable): mixed {
        if ($variable === 'meter_serial') {
            $variable = $this->meter->serial_number;
        }

        return $variable;
    }
}

<?php

namespace Inensus\SparkMeter\Sms\BodyParsers;

use App\Sms\BodyParsers\SmsBodyParser;

class SparkSmsMeterResetFailedFeedbackBody extends SmsBodyParser {
    protected $variables = ['meter_serial'];

    public function __construct(protected mixed $data) {}

    protected function getVariableValue(string $variable): mixed {
        if ($variable === 'meter_serial') {
            $variable = $this->data['meter'];
        }

        return $variable;
    }
}

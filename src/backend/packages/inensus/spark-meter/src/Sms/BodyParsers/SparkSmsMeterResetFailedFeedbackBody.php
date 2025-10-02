<?php

namespace Inensus\SparkMeter\Sms\BodyParsers;

use App\Sms\BodyParsers\SmsBodyParser;

class SparkSmsMeterResetFailedFeedbackBody extends SmsBodyParser {
    protected $variables = ['meter_serial'];
    protected $data;

    public function __construct($data) {
        $this->data = $data;
    }

    protected function getVariableValue(string $variable): mixed {
        if ($variable === 'meter_serial') {
            $variable = $this->data['meter'];
        }

        return $variable;
    }
}

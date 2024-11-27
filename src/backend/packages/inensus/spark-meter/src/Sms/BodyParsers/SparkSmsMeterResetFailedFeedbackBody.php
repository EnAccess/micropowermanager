<?php

namespace Inensus\SparkMeter\Sms\BodyParsers;

use App\Sms\BodyParsers\SmsBodyParser;

class SparkSmsMeterResetFailedFeedbackBody extends SmsBodyParser {
    protected $variables = ['meter_serial'];
    protected $data;

    public function __construct($data) {
        $this->data = $data;
    }

    protected function getVariableValue($variable) {
        switch ($variable) {
            case 'meter_serial':
                $variable = $this->data['meter'];
                break;
        }

        return $variable;
    }
}

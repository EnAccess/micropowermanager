<?php

namespace App\Sms\BodyParsers;

use Exception;

abstract class SmsBodyParser
{
    protected $variables;
    public function parseSms($body)
    {
        foreach ($this->variables as $variable) {
            $body =  str_replace('[' . $variable . ']', $this->getVariableValue($variable), $body);
        }
        return $body;
    }
    protected function getVariableValue($varialbe)
    {
        return new Exception("implement it on each class");
    }
}

<?php

namespace App\Sms\BodyParsers;

abstract class SmsBodyParser {
    /** @var array<int, string> */
    protected $variables;

    public function parseSms($body): string {
        foreach ($this->variables as $variable) {
            $body = str_replace('['.$variable.']', $this->getVariableValue($variable), $body);
        }

        return $body;
    }

    protected function getVariableValue(string $variable): mixed {
        return new \Exception('implement it on each class');
    }
}

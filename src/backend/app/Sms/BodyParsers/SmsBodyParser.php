<?php

namespace App\Sms\BodyParsers;

abstract class SmsBodyParser {
    /** @var array<int, string> */
    protected $variables;

    public function parseSms(mixed $body): string {
        foreach ($this->variableValues() as $variable => $value) {
            $body = str_replace('['.$variable.']', $value, $body);
        }

        return $body;
    }

    /**
     * @return array<string, string> the value of each variable, in $variables order
     */
    public function variableValues(): array {
        $values = [];

        foreach ($this->variables as $variable) {
            $values[$variable] = (string) $this->getVariableValue($variable);
        }

        return $values;
    }

    protected function getVariableValue(string $variable): mixed {
        return new \Exception('implement it on each class');
    }
}

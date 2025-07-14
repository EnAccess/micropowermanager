<?php

namespace App\Sms\BodyParsers;

class ResendInformationLastTransactionNotFound extends SmsBodyParser {
    /** @var array<int, string> */
    protected $variables = ['meter'];

    /** @var array<string, mixed> */
    protected array $data;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data) {
        $this->data = $data;
    }

    protected function getVariableValue(string $variable): mixed {
        return $this->data['meter'];
    }
}

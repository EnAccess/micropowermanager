<?php

namespace App\Sms\BodyParsers;

class ResendInformationLastTransactionNotFound extends SmsBodyParser {
    /** @var array<int, string> */
    protected $variables = ['meter'];

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(protected array $data) {}

    protected function getVariableValue(string $variable): mixed {
        return $this->data['meter'];
    }
}

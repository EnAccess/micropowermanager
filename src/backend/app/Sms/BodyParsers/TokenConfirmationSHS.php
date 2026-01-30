<?php

namespace App\Sms\BodyParsers;

use App\Models\Token;
use App\Models\Transaction\Transaction;

/**
 * Token confirmation for SHS / PayGo: activation token + duration + device serial.
 * Transaction must have a token with token_type = time.
 */
class TokenConfirmationSHS extends SmsBodyParser {
    /**
     * @var array<int, string>
     */
    protected $variables = ['name', 'surname', 'token', 'duration', 'unit', 'device_serial'];

    public function __construct(
        protected Transaction $transaction,
        protected Token $token,
    ) {}

    protected function getVariableValue(string $variable): mixed {
        $person = $this->transaction->device?->person;
        if ($this->transaction->nonPaygoAppliance()->exists()) {
            $person = $this->transaction->nonPaygoAppliance()->first()->person;
        }

        return match ($variable) {
            'name' => $person->name,
            'surname' => $person->surname,
            'token' => $this->token->token,
            'duration' => $this->formatDuration($this->token->token_amount),
            'unit' => $this->token->token_unit ?? Token::UNIT_DAYS,
            'device_serial' => $this->transaction->message ?: '-',
            default => $variable,
        };
    }

    private function formatDuration(float $amount): string {
        $v = $amount;

        return $v === (float) (int) $v ? (string) (int) $v : number_format($v, 1, '.', '');
    }
}

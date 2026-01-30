<?php

namespace App\Sms\BodyParsers;

use App\Models\Token;
use App\Models\Transaction\Transaction;

/**
 * Token confirmation for meter (non-smart): token + energy (kWh) + meter serial.
 * Transaction must have a token with token_type = energy.
 */
class TokenConfirmationMeter extends SmsBodyParser {
    /**
     * @var array<int, string>
     */
    protected $variables = ['name', 'surname', 'token', 'energy', 'meter_serial'];

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
            'name' => $person->name ?? '',
            'surname' => $person->surname ?? '',
            'token' => $this->token->token,
            'energy' => $this->formatEnergy($this->token->token_amount),
            'meter_serial' => $this->transaction->message ?: '-',
            default => $variable,
        };
    }

    private function formatEnergy(float $amount): string {
        return number_format($amount, 3, '.', '');
    }
}

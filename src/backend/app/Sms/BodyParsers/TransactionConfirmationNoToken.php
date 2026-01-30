<?php

namespace App\Sms\BodyParsers;

use App\Models\Transaction\Transaction;

/**
 * Simple confirmation when there is no token (e.g. down payment, access rate only).
 * Accepts Transaction or array (e.g. from cron notifiers) for amount.
 */
class TransactionConfirmationNoToken extends SmsBodyParser {
    /**
     * @var array<int, string>
     */
    protected $variables = ['name', 'surname', 'amount'];

    /**
     * @param Transaction|array<string, mixed> $transaction
     */
    public function __construct(protected Transaction|array $transaction) {}

    protected function getVariableValue(string $variable): mixed {
        if ($this->transaction instanceof Transaction) {
            $person = $this->transaction->device?->person;
            if ($this->transaction->nonPaygoAppliance()->exists()) {
                $person = $this->transaction->nonPaygoAppliance()->first()->person;
            }

            return match ($variable) {
                'name' => $person->name ?? '',
                'surname' => $person->surname ?? '',
                'amount' => $this->transaction->amount,
                default => $variable,
            };
        }

        return match ($variable) {
            'name' => $this->transaction['name'] ?? '',
            'surname' => $this->transaction['surname'] ?? '',
            'amount' => (float) ($this->transaction['amount'] ?? 0),
            default => $variable,
        };
    }
}

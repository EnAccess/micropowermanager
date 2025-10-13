<?php

namespace App\Sms\BodyParsers;

use App\Models\Transaction\Transaction;

class SmsTransactionHeader extends SmsBodyParser {
    /**
     * @var array<int, string>
     */
    protected $variables = ['name', 'surname', 'transaction_amount'];

    public function __construct(protected Transaction $transaction) {}

    protected function getVariableValue(string $variable): mixed {
        $person = $this->transaction->device->person->first();

        return match ($variable) {
            'name' => $person->name,
            'surname' => $person->surname,
            'transaction_amount' => $this->transaction->amount,
            default => $variable,
        };
    }
}

<?php

namespace App\Sms\BodyParsers;

use App\Models\Transaction\Transaction;

class SmsTransactionHeader extends SmsBodyParser {
    /**
     * @var array<int, string>
     */
    protected $variables = ['name', 'surname', 'transaction_amount'];
    protected Transaction $transaction;

    public function __construct(Transaction $transaction) {
        $this->transaction = $transaction;
    }

    protected function getVariableValue(string $variable): mixed {
        $person = $this->transaction->device->person->first();
        switch ($variable) {
            case 'name':
                $variable = $person->name;
                break;
            case 'surname':
                $variable = $person->surname;
                break;
            case 'transaction_amount':
                $variable = $this->transaction->amount;
                break;
        }

        return $variable;
    }
}

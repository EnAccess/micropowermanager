<?php

namespace App\Sms\BodyParsers;

use App\Models\PaymentHistory;

class ResendInformation extends SmsBodyParser {
    protected $variables = ['meter', 'token', 'energy', 'amount'];

    public function __construct(protected PaymentHistory $paymentHistory) {}

    protected function getVariableValue(string $variable): mixed {
        /** @var mixed $token */
        $token = $this->paymentHistory->paidFor()->first();

        $transaction = $this->paymentHistory->transaction()->first();

        return match ($variable) {
            'meter' => $transaction->message,
            'token' => $token->token,
            'energy' => $token->energy,
            'amount' => $this->paymentHistory->amount,
            default => $variable,
        };
    }
}

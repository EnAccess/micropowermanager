<?php

namespace App\Sms\BodyParsers;

use App\Models\PaymentHistory;

class EnergyConfirmation extends SmsBodyParser {
    protected $variables = ['meter', 'token', 'energy', 'amount'];
    protected $paymentHistory;

    public function __construct(PaymentHistory $paymentHistory) {
        $this->paymentHistory = $paymentHistory;
    }

    protected function getVariableValue($variable): mixed {
        $token = $this->paymentHistory->paidFor()->first();
        $transaction = $this->paymentHistory->transaction()->first();
        switch ($variable) {
            case 'meter':
                $variable = $transaction->message;
                break;
            case 'token':
                $variable = $token->token;
                break;
            case 'energy':
                $variable = $token->energy;
                break;
            case 'amount':
                $variable = $this->paymentHistory->amount;
                break;
        }

        return $variable;
    }
}

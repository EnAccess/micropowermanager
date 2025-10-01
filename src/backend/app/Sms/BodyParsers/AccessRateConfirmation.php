<?php

namespace App\Sms\BodyParsers;

use App\Models\PaymentHistory;

class AccessRateConfirmation extends SmsBodyParser {
    protected $variables = ['amount'];
    protected PaymentHistory $paymentHistory;

    public function __construct(PaymentHistory $paymentHistory) {
        $this->paymentHistory = $paymentHistory;
    }

    protected function getVariableValue(string $variable): mixed {
        if ($variable === 'amount') {
            $variable = $this->paymentHistory->amount;
        }

        return $variable;
    }
}

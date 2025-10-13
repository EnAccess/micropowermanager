<?php

namespace App\Sms\BodyParsers;

use App\Models\PaymentHistory;

class AccessRateConfirmation extends SmsBodyParser {
    protected $variables = ['amount'];

    public function __construct(protected PaymentHistory $paymentHistory) {}

    protected function getVariableValue(string $variable): mixed {
        if ($variable === 'amount') {
            $variable = $this->paymentHistory->amount;
        }

        return $variable;
    }
}

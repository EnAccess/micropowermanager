<?php

namespace App\Sms\BodyParsers;

use App\Models\PaymentHistory;

class AccessRateConfirmation extends SmsBodyParser {
    protected $variables = ['amount'];
    protected $paymentHistory;

    public function __construct(PaymentHistory $paymentHistory) {
        $this->paymentHistory = $paymentHistory;
    }

    protected function getVariableValue($variable): mixed {
        switch ($variable) {
            case 'amount':
                $variable = $this->paymentHistory->amount;
                break;
        }

        return $variable;
    }
}

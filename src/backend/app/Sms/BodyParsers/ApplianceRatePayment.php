<?php

namespace App\Sms\BodyParsers;

use App\Models\ApplianceRate;
use App\Models\PaymentHistory;

class ApplianceRatePayment extends SmsBodyParser {
    protected $variables = ['appliance_type_name', 'amount'];

    public function __construct(
        protected PaymentHistory $paymentHistory,
    ) {}

    protected function getVariableValue(string $variable): mixed {
        return match ($variable) {
            'appliance_type_name' => (
                ($applianceRate = $this->paymentHistory->paidFor) instanceof ApplianceRate
                    ? $applianceRate->appliancePerson->appliance->applianceType
                    : null
            ),
            'amount' => $this->paymentHistory->amount,
            default => $variable,
        };
    }
}

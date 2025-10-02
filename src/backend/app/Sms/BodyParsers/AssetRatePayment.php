<?php

namespace App\Sms\BodyParsers;

use App\Models\PaymentHistory;

class AssetRatePayment extends SmsBodyParser {
    protected $variables = ['appliance_type_name', 'amount'];

    public function __construct(protected PaymentHistory $paymentHistory) {}

    protected function getVariableValue(string $variable): mixed {
        return match ($variable) {
            'appliance_type_name' => $this->paymentHistory->paidFor->assetPerson->assetType,
            'amount' => $this->paymentHistory->amount,
            default => $variable,
        };
    }
}

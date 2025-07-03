<?php

namespace App\Sms\BodyParsers;

use App\Models\PaymentHistory;

class AssetRatePayment extends SmsBodyParser {
    protected $variables = ['appliance_type_name', 'amount'];
    protected $paymentHistory;

    public function __construct(PaymentHistory $paymentHistory) {
        $this->paymentHistory = $paymentHistory;
    }

    protected function getVariableValue($variable): mixed {
        switch ($variable) {
            case 'appliance_type_name':
                $variable = $this->paymentHistory->paidFor->assetPerson->assetType;
                break;
            case 'amount':
                $variable = $this->paymentHistory->amount;
                break;
        }

        return $variable;
    }
}

<?php

namespace App\Utils;

use App\DTO\TransactionDataContainer;

class MinimumPurchaseAmountValidator {
    private float $restAmount;
    private TransactionDataContainer $transactionData;

    public function validate(TransactionDataContainer $transactionData, float $minimumPurchaseAmount): bool {
        $this->transactionData = $transactionData;
        $this->restAmount = $this->transactionData->transaction->amount;

        $applianceInstallmentPayer = resolve(ApplianceInstallmentPayer::class);
        $applianceInstallmentPayer->initialize($transactionData);
        $this->restAmount = $applianceInstallmentPayer->consumeAmount();

        $accessRatePayer = resolve(AccessRatePayer::class);
        $accessRatePayer->initialize($transactionData);
        $this->restAmount = $accessRatePayer->consumeAmount();

        return $this->restAmount >= $minimumPurchaseAmount;
    }
}

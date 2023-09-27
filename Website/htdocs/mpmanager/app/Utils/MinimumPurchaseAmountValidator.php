<?php

namespace App\Utils;

use App\Misc\TransactionDataContainer;

class MinimumPurchaseAmountValidator
{
    private float $restAmount;
    private TransactionDataContainer $transactionData;

    public function validate(TransactionDataContainer $transactionData, $minimumPurchaseAmount): bool
    {
        $this->transactionData = $transactionData;
        $this->restAmount = $this->transactionData->transaction->amount;

        $applianceInstallmentPayer = resolve('ApplianceInstallmentPayer');
        $applianceInstallmentPayer->initialize($transactionData);
        $this->restAmount = $applianceInstallmentPayer->consumeAmount();

        if($applianceInstallmentPayer->shsLoan){
            //that means money can consume until 0
            return true;
        }
        $accessRatePayer = resolve('AccessRatePayer');
        $accessRatePayer->initialize($transactionData);
        $this->restAmount = $accessRatePayer->consumeAmount();

        return $this->restAmount >= $minimumPurchaseAmount;
    }
}

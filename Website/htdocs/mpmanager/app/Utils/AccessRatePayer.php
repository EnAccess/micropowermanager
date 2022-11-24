<?php

namespace App\Utils;

use App\Misc\TransactionDataContainer;
use App\Models\AccessRate\AccessRatePayment;
use App\Models\Transaction\Transaction;
use App\Services\AccessRatePaymentService;
use App\Services\AccessRateService;

class AccessRatePayer implements IPayer
{
    const MINIMUM_AMOUNT = 0;
    private AccessRatePayment $accessRatePayment;
    private TransactionDataContainer $transactionData;
    private Transaction $transaction;
    private float $debtAmount;

    public function __construct(private AccessRatePaymentService $accessRatePaymentService)
    {
        $this->debtAmount = self::MINIMUM_AMOUNT;
    }

    public function initialize(TransactionDataContainer $transactionData)
    {
        $accessRatePayment = $this->accessRatePaymentService->getAccessRatePaymentByMeter($transactionData->meter);
        $this->debtAmount = $accessRatePayment ? $accessRatePayment->debt : 0;
        $this->accessRatePayment = $accessRatePayment;
        $this->transactionData = $transactionData;
        $this->transaction = $transactionData->transaction;

    }

    public function pay()
    {
        if ($this->debtAmount > self::MINIMUM_AMOUNT) { //there is unpaid amount

            if ($this->debtAmount > $this->transactionData->transaction->amount) {
                $this->debtAmount -= $this->transactionData->transaction->amount;
                $this->transactionData->transaction->amount = self::MINIMUM_AMOUNT;
            } else {
                $this->transactionData->transaction->amount -= $this->debtAmount;
                $this->debtAmount = self::MINIMUM_AMOUNT;
            }

            $this->accessRatePaymentService->update($this->accessRatePayment, ['debt' => $this->debtAmount]);
            $this->transactionData->accessRateDebt = $this->debtAmount;
            //add payment history for the client
            event('payment.successful', [
                'amount' => $this->debtAmount,
                'paymentService' => $this->transactionData->transaction->original_transaction_type,
                'paymentType' => 'access rate',
                'sender' => $this->transactionData->transaction->sender,
                'paidFor' => $this->transactionData->meter->accessRate(),
                'payer' => $this->transactionData->meterParameter->owner,
                'transaction' => $this->transactionData->transaction,
            ]);
        }
        return $this->transactionData;
    }


    public function consumeAmount()
    {
        $accessRatePayment = $this->transactionData->meter->accessRatePayment()->first();
        $accessRateDebt = $accessRatePayment ? $accessRatePayment->debt : 0;
        $this->transaction->amount -= $accessRateDebt;
        return $this->transaction->amount;
    }
}
<?php

namespace App\Utils;

use App\Misc\TransactionDataContainer;
use App\Models\AccessRate\AccessRatePayment;
use App\Models\Transaction\Transaction;
use App\Services\AccessRatePaymentService;

class AccessRatePayer {
    public const MINIMUM_AMOUNT = 0;
    private AccessRatePayment $accessRatePayment;
    private TransactionDataContainer $transactionData;
    private Transaction $transaction;
    private float $debtAmount;

    public function __construct(private AccessRatePaymentService $accessRatePaymentService) {
        $this->debtAmount = self::MINIMUM_AMOUNT;
    }

    public function initialize(TransactionDataContainer $container) {
        $meter = $container->device->device;
        $accessRatePayment = $this->accessRatePaymentService->getAccessRatePaymentByMeter($meter);

        if ($accessRatePayment) {
            $this->debtAmount = $accessRatePayment->debt;
            $this->accessRatePayment = $accessRatePayment;
        }

        $this->transactionData = $container;
        $this->transaction = $container->transaction;
    }

    public function pay() {
        $meter = $this->transactionData->device->device;
        $owner = $this->transactionData->device->person;
        if ($this->debtAmount > self::MINIMUM_AMOUNT) { // there is unpaid amount
            if ($this->debtAmount > $this->transactionData->transaction->amount) {
                $this->debtAmount -= $this->transactionData->transaction->amount;
                $this->transactionData->transaction->amount = self::MINIMUM_AMOUNT;
            } else {
                $this->transactionData->transaction->amount = (int) ($this->transactionData->transaction->amount - $this->debtAmount);
                $this->debtAmount = self::MINIMUM_AMOUNT;
            }

            $this->accessRatePaymentService->update($this->accessRatePayment, ['debt' => $this->debtAmount]);
            $this->transactionData->accessRateDebt = $this->debtAmount;
            // add payment history for the client
            event('payment.successful', [
                'amount' => $this->debtAmount,
                'paymentService' => $this->transactionData->transaction->original_transaction_type,
                'paymentType' => 'access rate',
                'sender' => $this->transactionData->transaction->sender,
                'paidFor' => method_exists($meter, 'accessRate') ? $meter->accessRate() : null,
                'payer' => $owner,
                'transaction' => $this->transactionData->transaction,
            ]);
        }

        return $this->transactionData;
    }

    public function consumeAmount() {
        $this->transaction->amount = (int) ($this->transaction->amount - $this->debtAmount);

        return $this->transaction->amount;
    }
}

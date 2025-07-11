<?php

namespace App\Listeners;

use App\Models\AccessRate\AccessRate;
use App\Models\Asset;
use App\Models\AssetRate;
use App\Models\Token;
use App\Services\AccessRatePaymentHistoryService;
use App\Services\ApplianceRatePaymentHistoryService;
use App\Services\PaymentHistoryService;
use App\Services\PersonPaymentHistoryService;
use App\Services\TransactionPaymentHistoryService;

class PaymentSuccessListener {
    public function __construct(
        private PaymentHistoryService $paymentHistoryService,
        private PersonPaymentHistoryService $personPaymentHistoryService,
        private ApplianceRatePaymentHistoryService $applianceRatePaymentHistoryService,
        private AccessRatePaymentHistoryService $accessRatePaymentHistoryService,
        private TransactionPaymentHistoryService $transactionPaymentHistoryService,
    ) {}

    /**
     * @param int    $amount
     * @param string $paymentService the name of the Payment gateway
     * @param        $paymentType
     * @param mixed  $sender         : The number or person who sent the money
     * @param mixed  $paidFor        the identifier for the payment. Ex; { LoanID, TokenID }
     * @param        $payer
     * @param        $transaction
     */
    public function onPaymentSuccess(
        $amount,
        $paymentService,
        $paymentType,
        $sender,
        $paidFor,
        $payer,
        $transaction,
    ): void {
        $paymentHistoryData = [
            'amount' => $amount,
            'payment_service' => $paymentService,
            'payment_type' => $paymentType,
            'sender' => $sender,
        ];
        $paymentHistory = $this->paymentHistoryService->make($paymentHistoryData);
        $paymentHistory->created_at = $transaction->created_at;
        $paymentHistory->updated_at = $transaction->updated_at;
        $this->personPaymentHistoryService->setAssignee($payer);
        $this->personPaymentHistoryService->setAssigned($paymentHistory);
        $this->personPaymentHistoryService->assign();

        switch (true) {
            case $paidFor instanceof AccessRate:
                $this->accessRatePaymentHistoryService->setAssignee($paidFor);
                $this->accessRatePaymentHistoryService->setAssigned($paymentHistory);
                $this->accessRatePaymentHistoryService->assign();
                break;
            case $paidFor instanceof AssetRate:
                $this->applianceRatePaymentHistoryService->setAssignee($paidFor);
                $this->applianceRatePaymentHistoryService->setAssigned($paymentHistory);
                $this->applianceRatePaymentHistoryService->assign();
                break;
            case $paidFor instanceof Asset:
                $paymentHistory->paid_for_type = Asset::class;
                $paymentHistory->paid_for_id = $paidFor->id;
                break;
            case $paidFor instanceof Token:
                $paymentHistory->paid_for_type = Token::class;
                $paymentHistory->paid_for_id = $paidFor->id;
                break;
        }

        $this->transactionPaymentHistoryService->setAssignee($transaction);
        $this->transactionPaymentHistoryService->setAssigned($paymentHistory);
        $this->transactionPaymentHistoryService->assign();
        $this->paymentHistoryService->save($paymentHistory);
    }

    public function handle(
        $amount,
        $paymentService,
        $paymentType,
        $sender,
        $paidFor,
        $payer,
        $transaction,
    ): void {
        $this->onPaymentSuccess(
            $amount,
            $paymentService,
            $paymentType,
            $sender,
            $paidFor,
            $payer,
            $transaction
        );
    }
}

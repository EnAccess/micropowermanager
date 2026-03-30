<?php

namespace App\Listeners;

use App\Events\PaymentSuccessEvent;
use App\Models\AccessRate\AccessRate;
use App\Models\Appliance;
use App\Models\ApplianceRate;
use App\Models\Person\Person;
use App\Models\Token;
use App\Models\Transaction\Transaction;
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

    public function onPaymentSuccess(
        int $amount,
        string $paymentService,
        string $paymentType,
        string $sender,
        AccessRate|ApplianceRate|Appliance|Token $paidFor,
        Person $payer,
        Transaction $transaction,
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
            case $paidFor instanceof ApplianceRate:
                $this->applianceRatePaymentHistoryService->setAssignee($paidFor);
                $this->applianceRatePaymentHistoryService->setAssigned($paymentHistory);
                $this->applianceRatePaymentHistoryService->assign();
                break;
            case $paidFor instanceof Appliance:
                $paymentHistory->paid_for_type = Appliance::class;
                $paymentHistory->paid_for_id = $paidFor->id;
                break;
                // @phpstan-ignore instanceof.alwaysTrue
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

    public function handle(PaymentSuccessEvent $event): void {
        $this->onPaymentSuccess(
            $event->amount,
            $event->paymentService,
            $event->paymentType,
            $event->sender,
            $event->paidFor,
            $event->payer,
            $event->transaction
        );
    }
}

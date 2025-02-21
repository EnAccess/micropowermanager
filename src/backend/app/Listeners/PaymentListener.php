<?php

namespace App\Listeners;

use App\Models\AccessRate\AccessRate;
use App\Models\Asset;
use App\Models\AssetRate;
use App\Models\Meter\Meter;
use App\Models\Token;
use App\Services\AccessRatePaymentHistoryService;
use App\Services\ApplianceRatePaymentHistoryService;
use App\Services\PaymentHistoryService;
use App\Services\PersonPaymentHistoryService;
use App\Services\TransactionPaymentHistoryService;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use MPM\Transaction\Provider\ITransactionProvider;

class PaymentListener {
    public function __construct(
        private PaymentHistoryService $paymentHistoryService,
        private PersonPaymentHistoryService $personPaymentHistoryService,
        private ApplianceRatePaymentHistoryService $applianceRatePaymentHistoryService,
        private AccessRatePaymentHistoryService $accessRatePaymentHistoryService,
        private TransactionPaymentHistoryService $transactionPaymentHistoryService,
    ) {}

    public function onEnergyPayment(ITransactionProvider $transactionProvider): void {
        $transaction = $transactionProvider->getTransaction();
        // get meter preferences
        try {
            $meter = Meter::query()->where('meter_id', $transaction->message)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            Log::critical('Unkown meterId', ['meter_id' => $transaction->message, 'amount' => $transaction->amount]);
            event('transaction.failed', $transactionProvider);
        }
    }

    public function onLoanPayment(string $customer_id, int $amount): void {}

    public function onPaymentFailed(): void {
        Log::debug('payment failed event');
    }

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

    public function subscribe(Dispatcher $events): void {
        $events->listen('payment.energy', 'App\Listeners\PaymentListener@onEnergyPayment');
        $events->listen('payment.failed', 'App\Listeners\PaymentListener@onPaymentFailed');
        $events->listen('payment.successful', 'App\Listeners\PaymentListener@onPaymentSuccess');
    }
}

<?php

namespace App\Listeners;

use App\Lib\ITransactionProvider;
use App\Models\Meter\MeterParameter;
use App\Models\PaymentHistory;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class PaymentListener
{
    public function onEnergyPayment(ITransactionProvider $transactionProvider): void
    {
        $transaction = $transactionProvider->getTransaction();
        //get meter preferences
        try {
            $meterParameter = MeterParameter::with('meter')->where('meter_id', $transaction->message)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            Log::critical('Unkown meterId', ["meter_id" => $transaction->message, "amount" => $transaction->amount]);
            event('transaction.failed', $transactionProvider);
        }
    }

    public function onLoanPayment(string $customer_id, int $amount): void
    {
    }


    public function onPaymentFailed(): void
    {
        Log::debug('payment failed event');
    }

    /**
     * @param int    $amount
     * @param string $paymentService the name of the Payment gateway
     * @param $paymentType
     * @param mixed  $sender         : The number or person who sent the money
     * @param mixed  $paidFor        the identifier for the payment. Ex; { LoanID, TokenID }
     * @param $payer
     * @param $transaction
     */
    public function onPaymentSuccess(
        $amount,
        $paymentService,
        $paymentType,
        $sender,
        $paidFor,
        $payer,
        $transaction
    ): void {
        //store payment history
        $paymentHistory = new PaymentHistory();
        $paymentHistory->amount = $amount;
        $paymentHistory->payment_service = $paymentService;
        $paymentHistory->payment_type = $paymentType;
        $paymentHistory->sender = $sender;
        $paymentHistory->payer()->associate($payer); //the related payer
        $paymentHistory->paidFor()->associate($paidFor); // Loan , Token {Energy} , AccessRate
        if ($transaction !== null) {
            $paymentHistory->transaction()->associate($transaction);

            if ($paymentHistory->payment_service === 'third_party_transaction') {
                $paymentHistory->created_at = $transaction->created_at;
                $paymentHistory->updated_at = $transaction->updated_at;
            }
        }

        $paymentHistory->save();
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen('payment.energy', 'App\Listeners\PaymentListener@onEnergyPayment');
        $events->listen('payment.failed', 'App\Listeners\PaymentListener@onPaymentFailed');
        $events->listen('payment.successful', 'App\Listeners\PaymentListener@onPaymentSuccess');
    }
}

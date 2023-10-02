<?php

namespace App\Listeners;

use App\Models\Payment\PaymentProfile;
use App\Models\PaymentHistory;
use App\Models\Person\Person;
use Carbon\Carbon;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

use function count;

class PaymentPeriodListener
{
    /**
     * @var PaymentProfile
     */
    public $paymentProfile;
    /**
     * @var PaymentHistory
     */
    public $history;

    /**
     * Create the event listener.
     *
     * @param PaymentProfile $paymentProfile
     * @param PaymentHistory $history
     */
    public function __construct(PaymentProfile $paymentProfile, PaymentHistory $history)
    {
        $this->paymentProfile = $paymentProfile;
        $this->history = $history;
    }

    public function recalculate(Person $person): void
    {
        $transactions = $person->payments()
            ->with('transaction')
            ->latest()->take(10)
            ->groupBy('transaction_id')->get();
        $totalAmount = 0;
        $difference = ((new Carbon($transactions[0]->created_at))
                ->diffInDays((new Carbon($transactions[count($transactions) - 1]->created_at)))) / count($transactions);
        foreach ($transactions as $transaction) {
            $totalAmount += $transaction->amount;
        }

        Log::debug("total transactions" . count($transactions));
        Log::debug("average payment per transaction : " . $totalAmount / count($transactions));
        Log::debug("average payment period: " . $difference);


        //   $this->history->
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen('payment.period.recalculate', '\App\Listeners\PaymentPeriodListener@recalculate');
    }
}

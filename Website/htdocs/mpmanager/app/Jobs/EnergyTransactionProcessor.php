<?php

namespace App\Jobs;

use App\Misc\TransactionDataContainer;
use App\Models\Transaction\Transaction;
use App\PaymentHandler\AccessRate;
use App\Services\SmsAndroidSettingService;
use App\Sms\Senders\SmsConfigs;
use App\Sms\SmsTypes;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EnergyTransactionProcessor implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private $transaction;

    /**
     * Create a new job instance.
     *
     * @param \App\Models\Transaction\Transaction $transaction
     */
    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //set transaction type to energy
        $this->transaction->type = 'energy';
        $this->transaction->save();

        try {
            //create an object for the token job
            $transactionData = TransactionDataContainer::initialize($this->transaction);
        } catch (\Exception $e) {
            event('transaction.failed', [$this->transaction, $e->getMessage()]);
            return;
        }

        $loanContainer = resolve('LoanDataContainerProvider');
        $loanContainer->initialize($transactionData->transaction);
        $transactionData->transaction->amount = $loanContainer->loanCost();
        $transactionData->totalAmount = $transactionData->transaction->amount;
        if (empty($loanContainer->paid_rates)) {
            $transactionData->paid_rates = $loanContainer->paid_rates;
        }
        if ($transactionData->transaction->amount > 0) {
            // pay if necessary access rate
            $transactionData = AccessRate::payAccessRate($transactionData);
        }
        if ($transactionData->transaction->amount > 0) {
            //give transaction to token processor
            $meterParameter = $transactionData->meterParameter;
            $transactionData->amount  = $transactionData->transaction->amount;
            $kWhToBeCharged = 0.0;
            // get piggy-bank energy
            try {
                $bankAccount = $meterParameter->socialTariffPiggyBank()->firstOrFail();
                // calculate the cost of savings. To achive that, the price (for kWh.) should converted to Wh. (/1000)
                // the price is x100 in the database to keep the price as integer. The last two digits are decimal parts
                $savingsCost = $bankAccount->savings * (($bankAccount->socialTariff->price / 1000) / 100);
                if ($transactionData->amount >= $savingsCost) {
                    $kWhToBeCharged += $bankAccount->savings / 1000;
                    $transactionData->amount -= $savingsCost;
                } else {
                    $transactionData->amount = 0;
                    $kWhToBeCharged += $bankAccount->savings / 1000;
                    $bankAccount->savings -= $transactionData->amount
                        / (($bankAccount->socialTariff->price / 1000) / 100);
                }
                $bankAccount->update();
            } catch (ModelNotFoundException $exception) {
                // meter has no piggy bank account
            }

            $transactionData->chargedEnergy = round($kWhToBeCharged, 1);
            TokenProcessor::dispatch($transactionData)
                ->allOnConnection('redis')
                ->onQueue(\config('services.queues.token'));
        } else {
            event('transaction.successful', [$transactionData->transaction]);
            SmsProcessor::dispatch(
                $transactionData->transaction,
                SmsTypes::TRANSACTION_CONFIRMATION,
                SmsConfigs::class
            )->allOnConnection('redis')->onQueue(\config('services.queues.sms'));
        }
    }
}

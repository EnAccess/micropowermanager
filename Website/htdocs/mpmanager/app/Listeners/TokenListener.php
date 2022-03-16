<?php

namespace App\Listeners;

use App\Jobs\SmsProcessor;
use App\Misc\TransactionDataContainer;
use App\Models\Meter\MeterToken;
use App\Services\SmsAndroidSettingService;
use App\Sms\Senders\SmsConfigs;
use App\Sms\SmsTypes;
use Exception;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

class TokenListener
{
    private $token;
    protected $tokenData;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(MeterToken $token)
    {
        $this->token = $token;
    }

    /**
     * @param TransactionDataContainer $transactionContainer
     */
    public function generateToken(TransactionDataContainer $transactionContainer): void
    {
        $api = null;
        //get Manufacturer APi
        try {
            $api = resolve($transactionContainer->manufacturer->api_name);
        } catch (Exception $e) {
            //no api found
            Log::critical(
                'No Api is registered for ' . $transactionContainer->manufacturer->name,
                ['id' => '34758734658734567885458923', 'message' => $e->getMessage()]
            );
            event('transaction.failed', $transactionContainer->transaction);
            return;
        }

        try {
            //try to find the token.
            $this->token = $transactionContainer->transaction->token()->first();

            if ($this->token === null) {
                //generate new one if token does not exist
                $this->token = $api->generateToken($transactionContainer);
                $this->token->transaction()->associate($transactionContainer->transaction);
                $this->token->meter()->associate($transactionContainer->meter);
                //save token
                $this->token->save();
            }

            //add token to tokenData
            $transactionContainer->token = $this->token;
        } catch (Exception $e) {
            Log::critical(
                $transactionContainer->manufacturer->name . ' Token listener fails ',
                ['id' => '4627573927', 'message' => $e->getMessage()]
            );
            event('transaction.failed', $transactionContainer->transaction);
            return;
        }

        //send token via sms

        SmsProcessor::dispatch(
            $transactionContainer->transaction,
            SmsTypes::TRANSACTION_CONFIRMATION,
            SmsConfigs::class
        )->allOnConnection('redis')->onQueue(\config('services.queues.sms'));

        //payment successful
        event(
            'payment.successful',
            [
                'amount' => $transactionContainer->transaction->amount,
                'paymentService' => 'vodacom', //$tokenData->transaction->owner_type,
                'paymentType' => 'energy',
                'sender' => $transactionContainer->transaction->sender,
                'paidFor' => $this->token,
                'payer' => $transactionContainer->meterParameter->owner,
                'transaction' => $transactionContainer->transaction,
            ]
        );
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen('token.generate', '\App\Listeners\TokenListener@generateToken');
    }
}

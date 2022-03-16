<?php

namespace App\Jobs;

use App\Misc\TransactionDataContainer;
use App\Models\Meter\MeterToken;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

use function config;

class TokenProcessor implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @var TransactionDataContainer
     */
    private $transactionContainer;

    /**
     * @var bool
     */

    private $reCreate;
    /**
     * @var int
     */
    private $counter;

    private const MAX_TRIES = 3;


    /**
     * Create a new job instance.
     *
     * @param TransactionDataContainer $container
     * @param bool $reCreate is a flag which determines to create a new token or not
     * @param int $counter
     */
    public function __construct(
        TransactionDataContainer $container,
        bool $reCreate = false,
        int $counter = self::MAX_TRIES
    ) {
        $this->transactionContainer = $container;
        $this->reCreate = $reCreate;
        $this->counter = $counter;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws Exception
     */
    public function handle(): void
    {
        try {
            $api = resolve($this->transactionContainer->manufacturer->api_name);
        } catch (\Exception $e) {
            //no api found
            Log::critical(
                'No Api is registered for ' . $this->transactionContainer->manufacturer->name,
                ['id' => '34758734658734567885458923', 'message' => $e->getMessage()]
            );
            event('transaction.failed', [$this->transactionContainer->transaction, $e->getMessage()]);
            return;
        }
        $token = $this->transactionContainer->transaction->token()->first();
        if ($token !== null & $this->reCreate === true) {
            $token->delete();
            $token = null;
        }
        //no token generated before
        if ($token === null) {
            try {
                $tokenData = $api->chargeMeter($this->transactionContainer);
            } catch (Exception $e) {
                if (self::MAX_TRIES > $this->counter) {
                    $this->counter++;
                    //re-queue the job in five seconds
                    self::dispatch(
                        $this->transactionContainer,
                        false,
                        $this->counter
                    )->allOnConnection('redis')->onQueue(config('services.queues.token'))->delay(5);
                    return;
                }
                Log::critical(
                    $this->transactionContainer->manufacturer->name . ' Token listener failed after  ' .
                    $this->counter . 'times ',
                    ['id' => '4627573927', 'message' => $e->getMessage()]
                );
                event(
                    'transaction.failed',
                    [
                        $this->transactionContainer->transaction,
                        'Manufacturer Api did not succeeded after 3 times with following error : ' . $e->getMessage()
                    ]
                );
                return;
            }

            $token = MeterToken::query()->make(
                [
                    'token' => $tokenData['token'],
                    'energy' => $tokenData['energy'],
                ]
            );

            $token->transaction()->associate($this->transactionContainer->transaction);
            $token->meter()->associate($this->transactionContainer->meter);
            //save token
            $token->save();
        }
        //add token to tokenData
        $this->transactionContainer->token = $token;

        // payment event
        event(
            'payment.successful',
            [
                'amount' => $this->transactionContainer->transaction->amount,
                'paymentService' => $this->transactionContainer->transaction->original_transaction_type,
                'paymentType' => 'energy',
                'sender' => $this->transactionContainer->transaction->sender,
                'paidFor' => $token,
                'payer' => $this->transactionContainer->meterParameter->owner,
                'transaction' => $this->transactionContainer->transaction,
            ]
        );
        event('transaction.successful', [$this->transactionContainer->transaction]);
    }
}

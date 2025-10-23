<?php

namespace App\Jobs;

use App\Events\PaymentSuccessEvent;
use App\Events\TransactionFailedEvent;
use App\Events\TransactionSuccessfulEvent;
use App\Misc\TransactionDataContainer;
use App\Models\AssetRate;
use App\Models\Token;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

const MAX_TRIES = 3;

class TokenProcessor extends AbstractJob {
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        int $companyId,
        private TransactionDataContainer $transactionContainer,
        private bool $reCreate = false,
        private int $counter = MAX_TRIES,
    ) {
        $this->onConnection('redis');
        $this->onQueue('token');

        parent::__construct($companyId);
    }

    public function executeJob(): void {
        try {
            $api = resolve($this->transactionContainer->manufacturer->api_name);
        } catch (\Exception $e) {
            $this->handleApiException($e);

            return;
        }

        $token = $this->handleExistingToken();

        if (!$token instanceof Token) {
            $this->generateToken($api);
        }
        if ($token instanceof Token) {
            $this->handlePaymentEvents($token);
        }
    }

    private function handleApiException(\Throwable $e): void {
        Log::critical(
            'No Api is registered for '.$this->transactionContainer->manufacturer->name,
            ['message' => $e->getMessage()]
        );
        event(new TransactionFailedEvent($this->transactionContainer->transaction, $e->getMessage()));
    }

    private function handleExistingToken(): ?Token {
        $token = $this->transactionContainer->transaction->token()->first();

        if ($token !== null && $this->reCreate) {
            $token->delete();
            $token = null;
        }

        return $token;
    }

    private function generateToken(mixed $api): void {
        try {
            $tokenData = $api->chargeDevice($this->transactionContainer);
        } catch (\Exception $e) {
            $this->handleTokenGenerationFailure($e);

            return;
        }

        $this->saveToken($tokenData);
    }

    private function handleTokenGenerationFailure(\Throwable $e): void {
        if (MAX_TRIES > $this->counter) {
            $this->retryTokenGeneration();

            return;
        }
        Log::critical(
            $this->transactionContainer->manufacturer->name.' Token listener failed after  '.
            $this->counter.' times ',
            ['message' => $e->getMessage()]
        );

        $this->handleRollbackInFailure();

        event(new TransactionFailedEvent(
            $this->transactionContainer->transaction,
            'Manufacturer Api did not succeed after 3 times with the following error: '.$e->getMessage(),
        ));
    }

    private function retryTokenGeneration(): void {
        ++$this->counter;
        self::dispatch(
            $this->companyId,
            $this->transactionContainer,
            false,
            $this->counter
        )->allOnConnection('redis')->onQueue(config('services.queues.token'))->delay(5);
    }

    /**
     * @param array<string, mixed> $tokenData
     */
    private function saveToken(array $tokenData): void {
        $token = Token::query()->make($tokenData);
        $token->device_id = $this->transactionContainer->device->id;
        $token->transaction()->associate($this->transactionContainer->transaction);
        $token->save();

        $this->handlePaymentEvents($token);
    }

    private function handlePaymentEvents(Token $token): void {
        $owner = $this->transactionContainer->device->person;

        event(new PaymentSuccessEvent(
            amount: (int) $this->transactionContainer->transaction->amount,
            paymentService: $this->transactionContainer->transaction->original_transaction_type,
            paymentType: 'energy',
            sender: $this->transactionContainer->transaction->sender,
            paidFor: $token,
            payer: $owner,
            transaction: $this->transactionContainer->transaction,
        ));

        event(new TransactionSuccessfulEvent($this->transactionContainer->transaction));
    }

    private function handleRollbackInFailure(): void {
        $paidRates = $this->transactionContainer->paidRates;
        collect($paidRates)->map(function (array $paidRate) {
            $assetRate = AssetRate::query()->find($paidRate['asset_rate_id']);
            $assetRate->remaining += $paidRate['paid'];
            $assetRate->update();
            $assetRate->save();
        });
        $paymentHistories = $this->transactionContainer->transaction->paymentHistories()->get();
        $paymentHistories->map(function ($paymentHistory) {
            $paymentHistory->delete();
        });
    }
}

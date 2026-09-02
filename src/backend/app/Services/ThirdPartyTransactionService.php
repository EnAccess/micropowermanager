<?php

namespace App\Services;

use App\Models\ApiKey;
use App\Models\Transaction\BasePaymentProviderTransaction;
use App\Models\Transaction\ThirdPartyTransaction;
use App\Models\Transaction\Transaction;
use App\Services\Interfaces\PaymentInitiator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Registers a transaction reported by an external party (e.g. a USSD app run by another
 * company) against one of our devices. Reuses ThirdPartyTransaction, the existing generic
 * "no more specific provider" transaction type, tagging it with the calling API key's name
 * so it is attributable in the transaction list.
 *
 * Only reachable via the external-transactions API (auth:api-key middleware); initiatePayment()
 * itself also requires a valid API key so this provider cannot be selected from the internal
 * payment UI, which authenticates with the 'api' JWT guard instead.
 */
class ThirdPartyTransactionService implements PaymentInitiator {
    public function __construct(
        private ThirdPartyTransaction $thirdPartyTransaction,
        private Transaction $transaction,
        private Request $request,
    ) {}

    /**
     * @return array{transaction: Transaction, provider_data: array<string, mixed>, process_immediately: bool}
     */
    public function initiatePayment(
        float $amount,
        string $sender,
        string $message,
        string $type,
        int $customerId,
        ?string $serialId = null,
        ?string $externalReference = null,
    ): array {
        $apiKey = $this->resolveApiKey();
        if ($apiKey === null) {
            throw new \InvalidArgumentException('Third-party transactions require a valid API key');
        }

        if ($externalReference === null || $externalReference === '') {
            throw new \InvalidArgumentException('external_reference is required to register a third-party transaction');
        }

        $existing = $this->thirdPartyTransaction->newQuery()
            ->where('transaction_id', $externalReference)
            ->first();

        if ($existing !== null) {
            return [
                'transaction' => $existing->transaction,
                'provider_data' => [],
                'process_immediately' => false,
            ];
        }

        $transaction = DB::transaction(function () use ($amount, $sender, $message, $type, $externalReference, $apiKey) {
            $thirdPartyTransaction = $this->thirdPartyTransaction->newQuery()->create([
                'transaction_id' => $externalReference,
                'status' => BasePaymentProviderTransaction::STATUS_SUCCESS,
                'description' => $apiKey->name,
            ]);

            $transaction = $this->transaction->newQuery()->make([
                'amount' => $amount,
                'sender' => $sender,
                'message' => $message,
                'type' => $type,
            ]);

            $transaction->originalTransaction()->associate($thirdPartyTransaction);
            $transaction->save();

            return $transaction;
        });

        return ['transaction' => $transaction, 'provider_data' => [], 'process_immediately' => true];
    }

    private function resolveApiKey(): ?ApiKey {
        $header = $this->request->header('Authorization');
        if (!$header || !Str::startsWith(Str::lower($header), 'bearer ')) {
            return null;
        }

        $token = trim(substr($header, 7));

        return ApiKey::query()->active()->where('token_hash', hash('sha256', $token))->first();
    }
}

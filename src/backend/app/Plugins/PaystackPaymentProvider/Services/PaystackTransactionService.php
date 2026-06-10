<?php

declare(strict_types=1);

namespace App\Plugins\PaystackPaymentProvider\Services;

use App\Jobs\ProcessPayment;
use App\Models\Address\Address;
use App\Models\Meter\Meter;
use App\Models\Transaction\BasePaymentProviderTransaction;
use App\Models\Transaction\Transaction;
use App\Plugins\PaystackPaymentProvider\Models\PaystackTransaction;
use App\Plugins\PaystackPaymentProvider\Modules\Api\PaystackApiService;
use App\Services\AbstractRedirectPaymentInitiatorService;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

/**
 * @extends AbstractRedirectPaymentInitiatorService<PaystackTransaction>
 */
class PaystackTransactionService extends AbstractRedirectPaymentInitiatorService {
    public function __construct(
        private Meter $meter,
        private Address $address,
        private Transaction $transaction,
        private PaystackTransaction $paystackTransaction,
        private PaystackApiService $paystackApiService,
    ) {
        parent::__construct(
            $this->meter,
            $this->address,
            $this->transaction,
            $this->paystackTransaction
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function initializeTransactionData(): array {
        return [
            'order_id' => Uuid::uuid4()->toString(),
            'reference_id' => Uuid::uuid4()->toString(),
            'serial_id' => $this->getSerialId(),
            'status' => PaystackTransaction::STATUS_REQUESTED,
            'currency' => 'NGN',
            'customer_id' => $this->getCustomerId(),
            'amount' => $this->getAmount(),
            'metadata' => [
                'serial_id' => $this->getSerialId(),
                'customer_id' => $this->getCustomerId(),
            ],
        ];
    }

    public function getByOrderId(string $orderId): ?PaystackTransaction {
        return $this->paystackTransaction->newQuery()->where('order_id', '=', $orderId)->first();
    }

    public function getByExternalTransactionId(string $externalTransactionId): ?PaystackTransaction {
        return $this->paystackTransaction->newQuery()->where('external_transaction_id', '=', $externalTransactionId)->first();
    }

    public function getByPaystackReference(string $reference): ?PaystackTransaction {
        return $this->paystackTransaction->newQuery()->where('paystack_reference', '=', $reference)->first();
    }

    public function create(array $paystackTransactionData): PaystackTransaction {
        try {
            // Run on the tenant connection so a failure rolls back the right database.
            DB::connection('tenant')->beginTransaction();

            /** @var PaystackTransaction $paystackTransaction */
            $paystackTransaction = $this->paystackTransaction->newQuery()->create($paystackTransactionData);

            // Get customer's phone number for sender field
            $customerPhone = $this->getCustomerPhoneByCustomerId($paystackTransaction->getCustomerId());
            $sender = $customerPhone ?: '';

            $paystackTransaction->transaction()->create([
                'amount' => $paystackTransaction->getAmount(),
                'sender' => $sender,
                'message' => $paystackTransaction->getDeviceSerial(),
                'type' => 'energy',
            ]);

            DB::connection('tenant')->commit();

            return $paystackTransaction;
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            throw $e;
        }
    }

    public function getPaystackTransaction(): PaystackTransaction {
        return $this->getPaymentAggregatorTransaction();
    }

    public function processSuccessfulPayment(int $companyId, PaystackTransaction $transaction): void {
        $id = $transaction->transaction->id;
        dispatch(new ProcessPayment($companyId, $id));
        $transaction->setStatus(PaystackTransaction::STATUS_SUCCESS);
        $transaction->save();
    }

    public function processFailedPayment(PaystackTransaction $transaction): void {
        $transaction->setStatus(PaystackTransaction::STATUS_FAILED);
        $transaction->save();

        $relatedTransaction = $transaction->transaction;
        if ($relatedTransaction) {
            $relatedTransaction->update(['status' => PaystackTransaction::STATUS_FAILED]);
        }
    }

    protected function resolveCurrency(): string {
        return config('paystack-payment-provider.currency.default', 'NGN');
    }

    protected function requestedStatus(): int {
        return PaystackTransaction::STATUS_REQUESTED;
    }

    /**
     * @param PaystackTransaction $transaction
     *
     * @return array<string, mixed>
     */
    protected function submitToProvider(BasePaymentProviderTransaction $transaction, string $sender): array {
        $result = $this->paystackApiService->initializeTransaction($transaction);
        if ($result['error']) {
            throw new \RuntimeException('Paystack initialization failed: '.$result['error']);
        }

        return [
            'redirect_url' => $result['redirectionUrl'],
            'reference' => $result['reference'],
        ];
    }
}

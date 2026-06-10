<?php

declare(strict_types=1);

namespace App\Plugins\PesapalPaymentProvider\Services;

use App\Jobs\ProcessPayment;
use App\Models\Address\Address;
use App\Models\Meter\Meter;
use App\Models\Transaction\BasePaymentProviderTransaction;
use App\Models\Transaction\Transaction;
use App\Plugins\PesapalPaymentProvider\Models\PesapalTransaction;
use App\Plugins\PesapalPaymentProvider\Modules\Api\PesapalApiService;
use App\Plugins\PesapalPaymentProvider\Modules\Api\Resources\GetTransactionStatusResource;
use App\Services\AbstractRedirectPaymentInitiatorService;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

/**
 * @extends AbstractRedirectPaymentInitiatorService<PesapalTransaction>
 */
class PesapalTransactionService extends AbstractRedirectPaymentInitiatorService {
    public function __construct(
        private Meter $meter,
        private Address $address,
        private Transaction $transaction,
        private PesapalTransaction $pesapalTransaction,
        private PesapalApiService $pesapalApiService,
        private PesapalCredentialService $credentialService,
    ) {
        parent::__construct(
            $this->meter,
            $this->address,
            $this->transaction,
            $this->pesapalTransaction
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
            'status' => PesapalTransaction::STATUS_REQUESTED,
            'currency' => $this->credentialService->getCredentials()->getCurrency(),
            'customer_id' => $this->getCustomerId(),
            'amount' => $this->getAmount(),
            'metadata' => [
                'serial_id' => $this->getSerialId(),
                'customer_id' => $this->getCustomerId(),
            ],
        ];
    }

    public function getByOrderId(string $orderId): ?PesapalTransaction {
        return $this->pesapalTransaction->newQuery()->where('order_id', '=', $orderId)->first();
    }

    public function getByExternalTransactionId(string $externalTransactionId): ?PesapalTransaction {
        return $this->pesapalTransaction->newQuery()->where('external_transaction_id', '=', $externalTransactionId)->first();
    }

    public function getByOrderTrackingId(string $orderTrackingId): ?PesapalTransaction {
        return $this->pesapalTransaction->newQuery()->where('order_tracking_id', '=', $orderTrackingId)->first();
    }

    public function getByMerchantReference(string $merchantReference): ?PesapalTransaction {
        return $this->pesapalTransaction->newQuery()->where('merchant_reference', '=', $merchantReference)->first();
    }

    public function getByReferenceId(string $referenceId): ?PesapalTransaction {
        return $this->pesapalTransaction->newQuery()->where('reference_id', '=', $referenceId)->first();
    }

    public function create(array $pesapalTransactionData): PesapalTransaction {
        try {
            DB::connection('tenant')->beginTransaction();

            /** @var PesapalTransaction $pesapalTransaction */
            $pesapalTransaction = $this->pesapalTransaction->newQuery()->create($pesapalTransactionData);

            $customerPhone = $this->getCustomerPhoneByCustomerId($pesapalTransaction->getCustomerId());
            $sender = $customerPhone ?: '';

            $pesapalTransaction->transaction()->create([
                'amount' => $pesapalTransaction->getAmount(),
                'sender' => $sender,
                'message' => $pesapalTransaction->getDeviceSerial(),
                'type' => 'energy',
            ]);

            DB::connection('tenant')->commit();

            return $pesapalTransaction;
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            throw $e;
        }
    }

    public function getPesapalTransaction(): PesapalTransaction {
        return $this->getPaymentAggregatorTransaction();
    }

    public function processSuccessfulPayment(int $companyId, PesapalTransaction $transaction): void {
        $id = $transaction->transaction->id;
        dispatch(new ProcessPayment($companyId, $id));
        $transaction->setStatus(PesapalTransaction::STATUS_SUCCESS);
        $transaction->save();
    }

    /**
     * TODO: Unlike PaystackTransactionService::processFailedPayment, this does not mark the
     * related base Transaction as failed. The two diverged during copy-paste; reconcile once
     * there is test coverage confirming the intended behaviour.
     */
    public function processFailedPayment(PesapalTransaction $transaction): void {
        $transaction->setStatus(PesapalTransaction::STATUS_FAILED);
        $transaction->save();
    }

    /**
     * Fetches the authoritative status from PesaPal and persists it on the
     * transaction. The same code path serves IPN callbacks and operator-driven
     * verify calls so a transaction is never left stuck in REQUESTED just
     * because PesaPal didn't fire an IPN (e.g. customer aborted on the gateway).
     *
     * @return array{status_code: ?int, status_description: string, amount: float, currency: string, payment_method: string, confirmation_code: string, merchant_reference: string, error: ?string}
     */
    public function syncStatusFromApi(PesapalTransaction $transaction, int $companyId): array {
        $status = $this->pesapalApiService->getTransactionStatus($transaction->getOrderTrackingId());
        if ($status['error'] !== null) {
            return $status;
        }

        if (!empty($status['confirmation_code'])) {
            $transaction->setExternalTransactionId($status['confirmation_code']);
            $transaction->save();
        }

        $this->applyStatusCode($transaction, $status['status_code'], $companyId);

        return $status;
    }

    protected function resolveCurrency(): string {
        $credential = $this->credentialService->getCredentials();

        return $credential->getCurrency() ?: config('pesapal-payment-provider.currency.default', 'KES');
    }

    protected function requestedStatus(): int {
        return PesapalTransaction::STATUS_REQUESTED;
    }

    /**
     * @param PesapalTransaction $transaction
     *
     * @return array<string, mixed>
     */
    protected function submitToProvider(BasePaymentProviderTransaction $transaction, string $sender): array {
        $result = $this->pesapalApiService->submitOrder($transaction, $sender);
        if ($result['error']) {
            throw new \RuntimeException('Pesapal initialization failed: '.$result['error']);
        }

        return [
            'redirect_url' => $result['redirect_url'],
            'order_tracking_id' => $result['order_tracking_id'],
            'merchant_reference' => $result['merchant_reference'],
        ];
    }

    private function applyStatusCode(PesapalTransaction $transaction, ?int $statusCode, int $companyId): void {
        switch ($statusCode) {
            case GetTransactionStatusResource::STATUS_COMPLETED:
                $this->processSuccessfulPayment($companyId, $transaction);
                break;
            case GetTransactionStatusResource::STATUS_FAILED:
            case GetTransactionStatusResource::STATUS_REVERSED:
                $this->processFailedPayment($transaction);
                break;
            case GetTransactionStatusResource::STATUS_INVALID:
                $transaction->setStatus(PesapalTransaction::STATUS_ABANDONED);
                $transaction->save();
                break;
        }
    }
}

<?php

namespace App\Services;

use App\DTO\TransactionDataContainer;
use App\Exceptions\TransactionAmountNotEnoughException;
use App\Exceptions\TransactionIsInvalidForProcessingIncomingRequestException;
use App\Jobs\ProcessPayment;
use App\Models\Address\Address;
use App\Models\Meter\Meter;
use App\Models\Person\Person;
use App\Models\Transaction\BasePaymentProviderTransaction;
use App\Models\Transaction\Transaction;
use App\Plugins\SteamaMeter\Exceptions\ModelNotFoundException;
use App\Traits\HasCrudOperations;
use App\Utils\MinimumPurchaseAmountValidator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * @template T of BasePaymentProviderTransaction
 */
abstract class AbstractPaymentAggregatorTransactionService {
    /** @use HasCrudOperations<T> */
    use HasCrudOperations;

    private const int MINIMUM_TRANSACTION_AMOUNT = 0;
    protected string $payerPhoneNumber;
    public protected(set) string $meterSerialNumber;
    public protected(set) float $minimumPurchaseAmount;
    public protected(set) int $customerId;
    public protected(set) float $amount;

    public function __construct(
        private Meter $meter,
        private Address $address,
        private Transaction $transaction,
        /** @var T */
        public private(set) BasePaymentProviderTransaction $paymentProviderTransaction,
    ) {}

    /**
     * @return T
     */
    protected function crudModel(): BasePaymentProviderTransaction {
        return $this->paymentProviderTransaction;
    }

    public function validatePaymentOwner(string $meterSerialNumber, float $amount): void {
        if (!($meter = $this->meter->findBySerialNumber($meterSerialNumber)) instanceof Meter) {
            throw new ModelNotFoundException('Meter not found with serial number you entered');
        }

        $meterTariff = $meter->tariff;

        $customerId = $meter->device->person->id;

        if (!$customerId) {
            throw new ModelNotFoundException('Customer not found with meter serial number you entered');
        }

        $this->meterSerialNumber = $meterSerialNumber;
        $this->minimumPurchaseAmount = $meterTariff->minimum_purchase_amount ?? self::MINIMUM_TRANSACTION_AMOUNT;
        $this->customerId = $customerId;
        $this->amount = $amount;

        $this->payerPhoneNumber = $this->getTransactionSender($meterSerialNumber);
    }

    /**
     * @param array<string, mixed> $transactionData
     *
     * @throws TransactionIsInvalidForProcessingIncomingRequestException
     * @throws TransactionAmountNotEnoughException
     */
    public function imitateTransactionForValidation(array $transactionData): void {
        $newTransaction = $this->paymentProviderTransaction->newQuery()->make($transactionData);

        $this->paymentProviderTransaction = $newTransaction;

        $this->transaction = $this->transaction->newQuery()->make([
            'amount' => $transactionData['amount'],
            'sender' => $this->payerPhoneNumber,
            'message' => $this->meterSerialNumber,
            'type' => 'energy',
            'original_transaction_type' => $this->paymentProviderTransaction::class,
        ]);

        $this->isImitationTransactionValid($this->transaction);
    }

    public function saveTransaction(): void {
        $this->paymentProviderTransaction->save();
        $paymentAggregatorTransaction = $this->paymentProviderTransaction;
        $this->transaction->originalTransaction()->associate($paymentAggregatorTransaction)->save();
    }

    /**
     * Credit a payment exactly once.
     *
     * Payment providers redeliver webhooks freely, and our own status polls run alongside
     * those deliveries, so the row is locked and re-read before the transition: a second
     * notification finds STATUS_SUCCESS and returns without queueing another ProcessPayment.
     *
     *
     * @param T $transaction may carry unsaved provider data (confirmation code, M-PESA
     *                       receipt, transaction reference) — it is written to the locked
     *                       row on replays too, so a late payload still enriches the record
     */
    public function processSuccessfulPayment(int $companyId, BasePaymentProviderTransaction $transaction): void {
        DB::connection('tenant')->transaction(function () use ($companyId, $transaction): void {
            $lockedTransaction = $this->lockForSettlement($transaction);

            if ($lockedTransaction->status === BasePaymentProviderTransaction::STATUS_SUCCESS) {
                if ($lockedTransaction->isDirty()) {
                    $lockedTransaction->save();
                }

                return;
            }

            $mpmTransaction = $lockedTransaction->transaction()->first();
            if (!$mpmTransaction instanceof Transaction) {
                throw new \RuntimeException($lockedTransaction::class." {$lockedTransaction->getKey()} is missing its MPM transaction.");
            }

            $lockedTransaction->status = BasePaymentProviderTransaction::STATUS_SUCCESS;
            $lockedTransaction->save();

            dispatch(new ProcessPayment($companyId, $mpmTransaction->id));
        });

        $transaction->refresh();
    }

    /**
     * @param T $transaction
     */
    public function processFailedPayment(
        BasePaymentProviderTransaction $transaction,
        int $status = BasePaymentProviderTransaction::STATUS_FAILED,
    ): void {
        DB::connection('tenant')->transaction(function () use ($transaction, $status): void {
            $lockedTransaction = $this->lockForSettlement($transaction);

            // A success is final. A failure or timeout notification that arrives after the
            // payment went through must not erase value the customer already received.
            if ($lockedTransaction->status !== BasePaymentProviderTransaction::STATUS_SUCCESS) {
                $lockedTransaction->status = $status;
            }

            if ($lockedTransaction->isDirty()) {
                $lockedTransaction->save();
            }
        });

        $transaction->refresh();
    }

    /**
     * Compare what the provider says was paid against what we recorded when the payment was
     * initiated, so a tampered or misrouted notification cannot credit the wrong amount.
     *
     * @param array{amount: float, currency: ?string, reference: ?string} $expected the stored transaction
     * @param array{amount: float, currency: ?string, reference: ?string} $reported what the provider reports;
     *                                                                              a null field is one this provider does not send
     *
     * @return string|null the first mismatch found, or null when the notification is trustworthy
     */
    protected function paymentMismatch(string $providerLabel, array $expected, array $reported): ?string {
        if ($reported['reference'] !== null
            && ($expected['reference'] === null
                || !hash_equals($expected['reference'], $reported['reference']))) {
            return $providerLabel.' merchant reference does not match the stored transaction.';
        }

        if ($reported['currency'] !== null
            && strcasecmp((string) $expected['currency'], $reported['currency']) !== 0) {
            return $providerLabel.' currency does not match the stored transaction.';
        }

        if (number_format($expected['amount'], 2, '.', '') !== number_format($reported['amount'], 2, '.', '')) {
            return $providerLabel.' amount does not match the stored transaction.';
        }

        return null;
    }

    /**
     * A mismatch means the notification could not be verified, not that the payment failed, so
     * the status is deliberately left alone. The conflict is how the rest of the codebase flags a
     * transaction that needs a human — see ITransactionProvider::addConflict.
     *
     * @param T $transaction
     */
    protected function recordPaymentConflict(BasePaymentProviderTransaction $transaction, string $mismatch): void {
        $transaction->conflicts()->create(['state' => $mismatch]);
    }

    /**
     * @param T $transaction
     *
     * @return T
     */
    private function lockForSettlement(BasePaymentProviderTransaction $transaction): BasePaymentProviderTransaction {
        /** @var T $lockedTransaction */
        $lockedTransaction = $this->paymentProviderTransaction
            ->newQuery()
            ->lockForUpdate()
            ->findOrFail($transaction->getKey());

        // The status transition belongs to this service, never to the caller's instance.
        $providerData = Arr::except($transaction->getDirty(), ['status']);
        if ($providerData !== []) {
            // getDirty() hands back raw, already-cast-encoded values, so they are merged at the
            // raw-attribute level. Replaying them through setAttribute would json_encode an
            // array cast a second time (response_data, metadata) and store a nested string.
            $lockedTransaction->setRawAttributes(
                array_merge($lockedTransaction->getAttributes(), $providerData)
            );
        }

        return $lockedTransaction;
    }

    private function isImitationTransactionValid(Transaction $transaction): void {
        $transactionData = TransactionDataContainer::initialize($transaction);

        $validator = resolve(MinimumPurchaseAmountValidator::class);

        try {
            if (!$validator->validate($transactionData, $this->minimumPurchaseAmount)) {
                throw new TransactionAmountNotEnoughException('Transaction amount is not enough');
            }
        } catch (TransactionAmountNotEnoughException $e) {
            throw new TransactionAmountNotEnoughException($e->getMessage(), $e->getCode(), $e);
        } catch (\Exception) {
            throw new TransactionIsInvalidForProcessingIncomingRequestException('Invalid Transaction request.');
        }
    }

    private function getTransactionSender(string $meterSerialNumber): string {
        $meter = $this->meter->newQuery()
            ->where(
                'serial_number',
                $meterSerialNumber
            )->first();

        $personId = $meter->device->person->id;

        try {
            $address = $this->address->newQuery()
                ->whereHasMorph(
                    'owner',
                    [Person::class],
                    function ($q) use ($personId) {
                        $q->where('owner_id', $personId);
                    }
                )->where('is_primary', 1)->firstOrFail();

            return $address->phone;
        } catch (ModelNotFoundException $exception) {
            throw new \Exception('No phone number record found by customer.', $exception->getCode(), $exception);
        }
    }
}

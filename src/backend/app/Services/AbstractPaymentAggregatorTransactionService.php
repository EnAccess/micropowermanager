<?php

namespace App\Services;

use App\DTO\TransactionDataContainer;
use App\Exceptions\TransactionAmountNotEnoughException;
use App\Exceptions\TransactionIsInvalidForProcessingIncomingRequestException;
use App\Models\Address\Address;
use App\Models\Meter\Meter;
use App\Models\Person\Person;
use App\Models\Transaction\BasePaymentProviderTransaction;
use App\Models\Transaction\Transaction;
use App\Plugins\SteamaMeter\Exceptions\ModelNotFoundException;
use App\Traits\HasCrudOperations;
use App\Utils\MinimumPurchaseAmountValidator;

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

        try {
            $this->payerPhoneNumber = $this->getTransactionSender($meterSerialNumber);
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage(), $exception->getCode(), $exception);
        }
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

    private function isImitationTransactionValid(Transaction $transaction): void {
        try {
            $transactionData = TransactionDataContainer::initialize($transaction);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }

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

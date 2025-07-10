<?php

namespace App\Services;

use App\Exceptions\TransactionAmountNotEnoughException;
use App\Exceptions\TransactionIsInvalidForProcessingIncomingRequestException;
use App\Misc\TransactionDataContainer;
use App\Models\Address\Address;
use App\Models\Meter\Meter;
use App\Models\Person\Person;
use App\Models\Transaction\PaymentProviderTransactionInterface;
use App\Models\Transaction\Transaction;
use Inensus\SteamaMeter\Exceptions\ModelNotFoundException;

abstract class AbstractPaymentAggregatorTransactionService {
    private const MINIMUM_TRANSACTION_AMOUNT = 0;
    protected string $payerPhoneNumber;
    protected string $meterSerialNumber;
    protected float $minimumPurchaseAmount;
    protected int $customerId;
    protected float $amount;

    public function __construct(
        private Meter $meter,
        private Address $address,
        private Transaction $transaction,
        private PaymentProviderTransactionInterface $paymentAggregatorTransaction,
    ) {}

    public function validatePaymentOwner(string $meterSerialNumber, float $amount): void {
        if (!$meter = $this->meter->findBySerialNumber($meterSerialNumber)) {
            throw new ModelNotFoundException('Meter not found with serial number you entered');
        }

        $meterTariff = $meter->tariff;
        if (!($meterTariff instanceof \App\Models\Meter\MeterTariff)) {
            throw new ModelNotFoundException('Tariff not found with meter serial number you entered');
        }

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
            throw new \Exception($exception->getMessage());
        }
    }

    /**
     * @param array<string, mixed> $transactionData
     *
     * @throws TransactionIsInvalidForProcessingIncomingRequestException
     * @throws TransactionAmountNotEnoughException
     */
    public function imitateTransactionForValidation(array $transactionData): void {
        $this->paymentAggregatorTransaction = $this->paymentAggregatorTransaction->newQuery()->make($transactionData);
        $this->transaction = $this->transaction->newQuery()->make([
            'amount' => $transactionData['amount'],
            'sender' => $this->payerPhoneNumber,
            'message' => $this->meterSerialNumber,
            'type' => 'energy',
            'original_transaction_type' => $this->paymentAggregatorTransaction::class,
        ]);

        $this->isImitationTransactionValid($this->transaction);
    }

    public function saveTransaction(): void {
        $this->paymentAggregatorTransaction->save();
        /** @var \Illuminate\Database\Eloquent\Model $paymentAggregatorTransaction */
        $paymentAggregatorTransaction = $this->paymentAggregatorTransaction;
        $this->transaction->originalTransaction()->associate($paymentAggregatorTransaction)->save();
    }

    private function isImitationTransactionValid(Transaction $transaction): void {
        try {
            $transactionData = TransactionDataContainer::initialize($transaction);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        $validator = resolve('MinimumPurchaseAmountValidator');

        try {
            if (!$validator->validate($transactionData, $this->getMinimumPurchaseAmount())) {
                throw new TransactionAmountNotEnoughException('Transaction amount is not enough');
            }
        } catch (TransactionAmountNotEnoughException $e) {
            throw new TransactionAmountNotEnoughException($e->getMessage());
        } catch (\Exception $e) {
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
            throw new \Exception('No phone number record found by customer.');
        }
    }

    public function getCustomerId(): int {
        return $this->customerId;
    }

    public function getMeterSerialNumber(): string {
        return $this->meterSerialNumber;
    }

    public function getAmount(): float {
        return $this->amount;
    }

    public function getMinimumPurchaseAmount(): float {
        return $this->minimumPurchaseAmount;
    }

    public function getPaymentAggregatorTransaction(): PaymentProviderTransactionInterface {
        return $this->paymentAggregatorTransaction;
    }
}

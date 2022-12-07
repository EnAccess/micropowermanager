<?php

namespace App\Services;

use App\Exceptions\TransactionAmountNotEnoughException;
use App\Exceptions\TransactionIsInvalidForProcessingIncomingRequestException;
use App\Misc\TransactionDataContainer;
use App\Models\Address\Address;
use App\Models\BaseModel;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterParameter;
use App\Models\Person\Person;
use App\Models\Transaction\IRawTransaction;
use App\Models\Transaction\Transaction;
use Inensus\SteamaMeter\Exceptions\ModelNotFoundException;


abstract class AbstractPaymentAggregatorTransactionService
{
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
        private MeterParameter $meterParameter,
        private IRawTransaction $paymentAggregatorTransaction,
    ) {

    }

    public function validatePaymentOwner(string $meterSerialNumber, float $amount): void
    {
        if (!$meter = $this->meter->findBySerialNumber($meterSerialNumber)) {
            throw new ModelNotFoundException('Meter not found with serial number you entered');
        }

        if (!$meterTariff = $meter->meterParameter->tariff) {
            throw new ModelNotFoundException('Tariff not found with meter serial number you entered' );
        }

        $customerId = $meter->MeterParameter->owner_id;

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
            throw  new \Exception($exception->getMessage());
        }
    }


    /**
     * @throws TransactionIsInvalidForProcessingIncomingRequestException
     * @throws TransactionAmountNotEnoughException
     */
    public function imitateTransactionForValidation(array $transactionData)
    {
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

    public function saveTransaction()
    {
        $this->paymentAggregatorTransaction->save();
        $this->transaction->originalTransaction()->associate($this->paymentAggregatorTransaction)->save();
    }

    private function isImitationTransactionValid($transaction)
    {
        try {
            $transactionData = TransactionDataContainer::initialize($transaction);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        $validator = resolve('MinimumPurchaseAmountValidator');

        try {
            if (!$validator->validate($transactionData, $this->getMinimumPurchaseAmount())) {
                throw new TransactionAmountNotEnoughException("Transaction amount is not enough");
            }
        } catch (TransactionAmountNotEnoughException $e) {
            throw new TransactionAmountNotEnoughException($e->getMessage());
        }
        catch (\Exception $e) {
            throw new TransactionIsInvalidForProcessingIncomingRequestException(("Invalid Transaction request."));
        }
    }

    private function getTransactionSender($meterSerialNumber)
    {
        $meterParameter = $this->meterParameter->newQuery()
            ->whereHas('meter',
                function ($q) use ($meterSerialNumber) {
                    $q->where('serial_number', $meterSerialNumber);
                })->first();

        $personId = $meterParameter->owner_id;
        try {
            $address = $this->address->newQuery()
                ->whereHasMorph('owner', [Person::class],
                    function ($q) use ($personId) {
                        $q->where('owner_id', $personId);
                    })->where('is_primary', 1)->firstOrFail();
            return $address->phone;
        } catch (ModelNotFoundException $exception) {
            throw new \Exception('No phone number record found by customer.');
        }
    }

    public function getCustomerId(): int
    {
        return $this->customerId;
    }

    public function getMeterSerialNumber()
    {
        return $this->meterSerialNumber;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function getMinimumPurchaseAmount()
    {
        return $this->minimumPurchaseAmount;
    }

    public function getPaymentAggregatorTransaction(): IRawTransaction
    {
        return $this->paymentAggregatorTransaction;
    }

}
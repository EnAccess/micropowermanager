<?php

namespace App\Services;

use App\Exceptions\TransactionAmountNotEnoughException;
use App\Exceptions\TransactionIsInvalidForProcessingIncomingRequestException;
use App\Misc\TransactionDataContainer;
use App\Models\Address\Address;
use App\Models\BaseModel;
use App\Models\Device;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterParameter;
use App\Models\Person\Person;
use App\Models\Transaction\IRawTransaction;
use App\Models\Transaction\Transaction;
use Inensus\SteamaMeter\Exceptions\ModelNotFoundException;
use MPM\Device\DeviceService;

abstract class AbstractPaymentAggregatorTransactionService
{
    protected Person $owner;
    private const MINIMUM_TRANSACTION_AMOUNT = 0;
    protected string $payerPhoneNumber;
    protected string $serialNumber;
    protected float $minimumPurchaseAmount = 0;
    protected float $amount;

    public function __construct(
        private DeviceService $deviceService,
        private Address $address,
        private Transaction $transaction,
        private IRawTransaction $paymentAggregatorTransaction,
    ) {
    }

    public function validatePaymentOwner(string $serialNumber, float $amount): void
    {
        if (!$device = $this->deviceService->getBySerialNumber($serialNumber)) {
            throw new ModelNotFoundException('Device not found with serial number you entered');
        }

        if (!$owner = $device->person) {
            throw new ModelNotFoundException('Customer not found with serial number you entered');
        }

        $deviceType = $device->device_type;
        $isMeter = $deviceType == Meter::RELATION_NAME;

        if ($isMeter) {
            $tariff = $device->device->tariff()->first();

            if (!$tariff) {
                throw new ModelNotFoundException('Tariff not found with meter serial number you entered');
            }
            $this->minimumPurchaseAmount = $tariff->minimum_purchase_amount ?? self::MINIMUM_TRANSACTION_AMOUNT;
        }

        $this->owner = $owner;
        $this->serialNumber = $serialNumber;
        $this->amount = $amount;

        try {
            $this->payerPhoneNumber = $this->getTransactionSender();
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
            'message' => $this->serialNumber,
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
        } catch (\Exception $e) {
            throw new TransactionIsInvalidForProcessingIncomingRequestException(("Invalid Transaction request."));
        }
    }

    private function getTransactionSender()
    {
        $personId = $this->owner->id;
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

    public function getCustomerId(): int
    {
        return $this->owner->id;
    }

    public function getSerialNumber()
    {
        return $this->serialNumber;
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

    public function getTransaction(): Transaction
    {
        return $this->transaction;
    }

    public function getPayerPhoneNumber()
    {
        return $this->payerPhoneNumber;
    }
}

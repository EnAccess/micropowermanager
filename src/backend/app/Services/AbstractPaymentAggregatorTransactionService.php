<?php

namespace App\Services;

use App\DTO\TransactionDataContainer;
use App\Exceptions\TransactionAmountNotEnoughException;
use App\Exceptions\TransactionIsInvalidForProcessingIncomingRequestException;
use App\Models\Address\Address;
use App\Models\Meter\Meter;
use App\Models\Person\Person;
use App\Models\SolarHomeSystem;
use App\Models\Transaction\BasePaymentProviderTransaction;
use App\Models\Transaction\Transaction;
use App\Plugins\SteamaMeter\Exceptions\ModelNotFoundException;
use App\Services\Interfaces\IBaseService;
use App\Traits\HasCrudOperations;
use App\Utils\MinimumPurchaseAmountValidator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @template T of BasePaymentProviderTransaction
 *
 * @implements IBaseService<T>
 */
abstract class AbstractPaymentAggregatorTransactionService implements IBaseService {
    /** @use HasCrudOperations<T> */
    use HasCrudOperations;

    private const MINIMUM_TRANSACTION_AMOUNT = 0;
    protected string $payerPhoneNumber;
    protected string $meterSerialNumber;
    protected float $minimumPurchaseAmount;
    protected int $customerId;
    protected float $amount;

    public function __construct(
        private Meter $meter,
        private SolarHomeSystem $solarHomeSystem,
        private Address $address,
        private Transaction $transaction,
        private BasePaymentProviderTransaction $paymentAggregatorTransaction,
    ) {}

    /**
     * @return T
     */
    protected function crudModel(): Model {
        return $this->paymentAggregatorTransaction;
    }

    public function validateMeterPayment(string $meterSerialNumber, float $amount): void {
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

    public function validatePaymentOwner(string $serialId, float $amount): void {
        if (!$this->validateMeterSerial($serialId)) {
            throw new \Exception('Invalid meter serial number');
        }

        $this->validateMeterPayment($serialId, $amount);

        if ($amount <= 0) {
            throw new \Exception('Invalid payment amount');
        }
    }

    public function validateMeterSerial(string $serialId): bool {
        $meter = $this->meter->newQuery()
            ->where('serial_number', $serialId)
            ->where('in_use', 1)
            ->first();

        return $meter !== null;
    }

    public function validateSHSSerial(string $serialId): bool {
        $shs = $this->solarHomeSystem->newQuery()
            ->where('serial_number', $serialId)
            ->first();

        return $shs !== null;
    }

    public function validateDeviceSerial(string $serialId, string $deviceType = 'meter'): bool {
        if ($deviceType === 'shs') {
            return $this->validateSHSSerial($serialId);
        }

        return $this->validateMeterSerial($serialId);
    }

    public function getCustomerIdByMeterSerial(string $serialId): ?int {
        $meter = $this->meter->newQuery()
            ->where('serial_number', $serialId)
            ->where('in_use', 1)
            ->first();

        if (!$meter) {
            return null;
        }

        return $meter->device->person->id;
    }

    public function getCustomerIdBySHSSerial(string $serialId): ?int {
        $shs = app()->make(SolarHomeSystem::class)
            ->newQuery()
            ->where('serial_number', $serialId)
            ->first();

        if (!$shs) {
            return null;
        }

        $device = $shs->device()->first();
        if (!$device || !$device->person) {
            return null;
        }

        return (int) $device->person->id;
    }

    public function getCustomerPhoneByCustomerId(int $customerId): ?string {
        try {
            $personService = app()->make(PersonService::class);
            $person = $personService->getById($customerId);

            return (string) $person->addresses->first()->phone;
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $transactionData
     *
     * @throws TransactionIsInvalidForProcessingIncomingRequestException
     * @throws TransactionAmountNotEnoughException
     */
    public function imitateTransactionForValidation(array $transactionData): void {
        $newTransaction = $this->paymentAggregatorTransaction->newQuery()->make($transactionData);

        $this->paymentAggregatorTransaction = $newTransaction;

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
        $paymentAggregatorTransaction = $this->paymentAggregatorTransaction;
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
            throw new TransactionAmountNotEnoughException($e->getMessage());
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

    /**
     * @return T
     */
    public function getPaymentAggregatorTransaction(): BasePaymentProviderTransaction {
        return $this->paymentAggregatorTransaction;
    }
}

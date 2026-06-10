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
use App\Services\Interfaces\IBaseService;
use App\Utils\MinimumPurchaseAmountValidator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @template T of BasePaymentProviderTransaction
 *
 * @implements IBaseService<T>
 */
abstract class AbstractPaymentAggregatorTransactionService implements IBaseService {
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
        private BasePaymentProviderTransaction $paymentAggregatorTransaction,
    ) {}

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

    /**
     * @return T|null
     */
    public function getById(int $id): ?Model {
        return $this->paymentAggregatorTransaction->newQuery()->find($id);
    }

    /**
     * @return Collection<int, T>
     */
    public function getByStatus(int $status): Collection {
        return $this->paymentAggregatorTransaction->newQuery()->where('status', '=', $status)->get();
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return T
     */
    public function create(array $data): Model {
        return $this->paymentAggregatorTransaction->newQuery()->create($data);
    }

    /**
     * @param T                    $model
     * @param array<string, mixed> $data
     *
     * @return T
     */
    public function update(Model $model, array $data): Model {
        $model->update($data);
        $model->fresh();

        return $model;
    }

    /**
     * @param T $model
     */
    public function delete(Model $model): ?bool {
        return $model->delete();
    }

    /**
     * @return Collection<int, T>|LengthAwarePaginator<int, T>
     */
    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        $query = $this->paymentAggregatorTransaction->newQuery();

        if ($limit) {
            return $query->paginate($limit);
        }

        return $query->get();
    }

    private function isImitationTransactionValid(Transaction $transaction): void {
        try {
            $transactionData = TransactionDataContainer::initialize($transaction);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }

        $validator = resolve(MinimumPurchaseAmountValidator::class);

        try {
            if (!$validator->validate($transactionData, $this->getMinimumPurchaseAmount())) {
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

    /**
     * @return T
     */
    public function getPaymentAggregatorTransaction(): BasePaymentProviderTransaction {
        return $this->paymentAggregatorTransaction;
    }

    protected function getMeter(): Meter {
        return $this->meter;
    }
}

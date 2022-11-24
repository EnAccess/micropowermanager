<?php
declare(strict_types=1);

namespace Inensus\WaveMoneyPaymentProvider\Modules\Transaction;

use App\Models\Address\Address;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterParameter;
use App\Models\Person\Person;
use App\Models\Transaction\Transaction;
use App\Services\AbstractPaymentAggregatorTransactionService;
use App\Services\IBaseService;
use Inensus\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;
use Ramsey\Uuid\Uuid;

class WaveMoneyTransactionService extends AbstractPaymentAggregatorTransactionService implements IBaseService
{

    private Meter $meter;
    private Address $address;
    private Transaction $transaction;
    private MeterParameter $meterParameter;
    private WaveMoneyTransaction $waveMoneyTransaction;

    public function __construct(
        Meter $meter,
        Address $address,
        Transaction $transaction,
        MeterParameter $meterParameter,
        WaveMoneyTransaction $waveMoneyTransaction,
    ) {
        $this->meterParameter = $meterParameter;
        $this->transaction = $transaction;
        $this->address = $address;
        $this->meter = $meter;
        $this->waveMoneyTransaction = $waveMoneyTransaction;

        parent::__construct(
            $this->meter,
            $this->address,
            $this->transaction,
            $this->meterParameter,
            $this->waveMoneyTransaction
        );
    }

    public function initializeTransactionData(): array
    {
        $orderId = Uuid::uuid4()->toString(); // need to store somewhere
        $referenceId = Uuid::uuid4()->toString(); // need to store somewhere

        return [
            'order_id' => $orderId,
            'reference_id' => $referenceId,
            'meter_serial' => $this->getMeterSerialNumber(),
            'status' => WaveMoneyTransaction::STATUS_REQUESTED,
            'currency' => 'MMK',
            'customer_id' => $this->getCustomerId(),
            'amount' => $this->getAmount()
        ];
    }

    public function getByOrderId(string $orderId)
    {
        return $this->waveMoneyTransaction->newQuery()->where('order_id', '=', $orderId)
            ->firstOrFail();
    }

    public function getByStatus($status)
    {
        return $this->waveMoneyTransaction->newQuery()->where('status', '=', $status)
            ->get();
    }

    public function getById($id)
    {
        return $this->waveMoneyTransaction->newQuery()->find($id);
    }

    public function update($waveMoneyTransaction, $waveMoneyTransactionData)
    {
        $waveMoneyTransaction->update($waveMoneyTransaction);
        $waveMoneyTransaction->fresh();

        return $waveMoneyTransaction;
    }

    public function create($waveMoneyTransactionData)
    {
        return $this->waveMoneyTransaction->newQuery()->create($waveMoneyTransactionData);
    }

    public function delete($waveMoneyTransaction)
    {
        return $waveMoneyTransaction->delete();
    }

    public function getAll($limit = null)
    {
        $query = $this->waveMoneyTransaction->newQuery();

        if ($limit) {
            return $query->paginate($limit);
        }

        return $this->waveMoneyTransaction->newQuery()->get();
    }

    public function getWaveMoneyTransaction()
    {
        return $this->getPaymentAggregatorTransaction();
    }
}

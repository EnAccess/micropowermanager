<?php
declare(strict_types=1);

namespace Inensus\WaveMoneyPaymentProvider\Modules\Transaction;

use App\Models\Address\Address;
use App\Models\Device;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterParameter;
use App\Models\Person\Person;
use App\Models\Transaction\Transaction;
use App\Services\AbstractPaymentAggregatorTransactionService;
use App\Services\IBaseService;
use Inensus\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;
use MPM\Device\DeviceService;
use Ramsey\Uuid\Uuid;

class WaveMoneyTransactionService extends AbstractPaymentAggregatorTransactionService implements IBaseService
{

    public function __construct(
        private DeviceService $deviceService,
        private Address $address,
        private Transaction $transaction,
        private WaveMoneyTransaction $waveMoneyTransaction,
    ) {

        parent::__construct(
            $this->deviceService,
            $this->address,
            $this->transaction,
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
            'meter_serial' => $this->getSerialNumber(),
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

    public function getByExternalTransactionId(string $externalTransactionId)
    {
        return $this->waveMoneyTransaction->newQuery()->where('external_transaction_id', '=', $externalTransactionId)
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
        $waveMoneyTransaction->update($waveMoneyTransactionData);
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

<?php

declare(strict_types=1);

namespace App\Plugins\WaveMoneyPaymentProvider\Modules\Transaction;

use App\Models\Address\Address;
use App\Models\Meter\Meter;
use App\Models\SolarHomeSystem;
use App\Models\Transaction\Transaction;
use App\Plugins\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;
use App\Services\AbstractPaymentAggregatorTransactionService;
use App\Services\Interfaces\IBaseService;
use Ramsey\Uuid\Uuid;

/**
 * @extends AbstractPaymentAggregatorTransactionService<WaveMoneyTransaction>
 *
 * @implements IBaseService<WaveMoneyTransaction>
 */
class WaveMoneyTransactionService extends AbstractPaymentAggregatorTransactionService implements IBaseService {
    public function __construct(
        private Meter $meter,
        private SolarHomeSystem $solarHomeSystem,
        private Address $address,
        private Transaction $transaction,
        private WaveMoneyTransaction $waveMoneyTransaction,
    ) {
        parent::__construct(
            $this->meter,
            $this->solarHomeSystem,
            $this->address,
            $this->transaction,
            $this->waveMoneyTransaction
        );
    }

    /**
     * @return array{
     *     order_id: string,
     *     reference_id: string,
     *     meter_serial: string,
     *     status: int,
     *     currency: string,
     *     customer_id: int,
     *     amount: float|int
     * }
     */
    public function initializeTransactionData(): array {
        $orderId = Uuid::uuid4()->toString(); // need to store somewhere
        $referenceId = Uuid::uuid4()->toString(); // need to store somewhere

        return [
            'order_id' => $orderId,
            'reference_id' => $referenceId,
            'meter_serial' => $this->meterSerialNumber,
            'status' => WaveMoneyTransaction::STATUS_REQUESTED,
            'currency' => 'MMK',
            'customer_id' => $this->customerId,
            'amount' => $this->amount,
        ];
    }

    public function getByOrderId(string $orderId): WaveMoneyTransaction {
        return $this->waveMoneyTransaction->newQuery()->where('order_id', '=', $orderId)
            ->firstOrFail();
    }

    public function getByExternalTransactionId(string $externalTransactionId): WaveMoneyTransaction {
        return $this->waveMoneyTransaction->newQuery()->where('external_transaction_id', '=', $externalTransactionId)
            ->firstOrFail();
    }

    public function getById(int $id): WaveMoneyTransaction {
        return $this->waveMoneyTransaction->newQuery()->findOrFail($id);
    }

    public function create($waveMoneyTransactionData): WaveMoneyTransaction {
        return $this->waveMoneyTransaction->newQuery()->create($waveMoneyTransactionData);
    }

    public function getWaveMoneyTransaction(): WaveMoneyTransaction {
        return $this->getPaymentAggregatorTransaction();
    }
}

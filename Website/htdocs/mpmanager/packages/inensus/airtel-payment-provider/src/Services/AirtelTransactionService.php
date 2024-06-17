<?php
namespace Inensus\AirtelPaymentProvider\Services;

use App\Models\Address\Address;
use App\Models\Transaction\AirtelTransaction;
use App\Models\Transaction\Transaction;
use App\Services\AbstractPaymentAggregatorTransactionService;
use App\Services\IBaseService;

use MPM\Device\DeviceService;

class AirtelTransactionService extends AbstractPaymentAggregatorTransactionService implements IBaseService
{

    public function __construct(
        private DeviceService $deviceService,
        private Address $address,
        private Transaction $transaction,
        private AirtelTransaction $airtelTransaction
    ) {
        parent::__construct(
            $this->deviceService,
            $this->address,
            $this->transaction,
            $this->airtelTransaction
        );
    }

    public function initializeTransactionData($transactionData): array
    {
        return [
            'amount' => $transactionData['AMOUNT'],
            'interface_id' => $transactionData['MERCHANTMSISDN'],
            'business_number' => $transactionData['MERCHANTMSISDN'],
            'status' => 2, // 2 for validated, waits for processing
            'trans_id' => "",
            'tr_id' => $transactionData['REFERENCE1'],
        ];

    }

    public function getByTrId($trId)
    {
        return $this->airtelTransaction->newQuery()->where('tr_id', $trId)->firstOrFail();
    }

    public function getById($id)
    {
        return $this->airtelTransaction->newQuery()->find($id);
    }


    public function update($airtelTransaction, $airtelTransactionData)
    {
        $airtelTransaction->update($airtelTransactionData);
        $airtelTransaction->fresh();

        return $airtelTransaction;
    }

    public function create($airtelTransactionData)
    {
        return $this->airtelTransaction->newQuery()->create($airtelTransactionData);
    }

    public function delete($airtelTransaction)
    {
        return $airtelTransaction->delete();
    }

    public function getAll($limit = null)
    {
        $query = $this->airtelTransaction->newQuery();

        if ($limit) {
            return $query->paginate($limit);
        }

        return $this->airtelTransaction->newQuery()->get();
    }
}
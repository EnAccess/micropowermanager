<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterParameter;
use App\Models\Transaction\AgentTransaction;
use App\Models\Transaction\Transaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

use function Symfony\Component\String\s;

class AgentTransactionService implements IBaseService
{
    public function __construct(
        private AgentTransaction $agentTransaction,
        private Transaction $transaction,
        private Device $device
    ) {
    }



    public function getAll($limit = null, $agentId = null, $forApp = false)
    {
        $query = $this->transaction->newQuery();

        if ($forApp) {
            $query->with(['originalAgent', 'device' => fn ($q) => $q->whereHas('person')->with(['device','person'])]);
        } else {
            $query->with(['device' => fn ($q) => $q->whereHas('person')->with(['device','person'])]);
        }

        $query->whereHasMorph(
            'originalTransaction',
            [AgentTransaction::class],
            static function ($q) use ($agentId) {
                $q->where('agent_id', $agentId);
            }
        );

        if ($limit) {
            return $query->paginate($limit);
        }
        return $query->get();
    }

    public function getById($agentId, $customerId = null)
    {
        $customerDeviceSerials = $this->device->newQuery()->where('person_id', $customerId)
            ->get()->pluck('device_serial');

        if (!$customerDeviceSerials->count()) {
            return null;
        }
        return $this->transaction->newQuery()
            ->with(['originalAgent', 'device' => fn ($q) => $q->whereHas('person')->with(['device','person'])])
            ->whereHasMorph(
                'originalTransaction',
                [AgentTransaction::class],
                fn ($q) => $q->where('agent_id', $agentId)
            )
            ->whereHas('device', fn ($q) => $q->whereIn('device_serial', $customerDeviceSerials))
            ->latest()->paginate();
    }

    public function create($transactionData)
    {
        return $this->agentTransaction->newQuery()->create($transactionData);
    }

    public function update($model, $data)
    {
        // TODO: Implement update() method.
    }

    public function delete($model)
    {
        // TODO: Implement delete() method.
    }
}

<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Transaction\AgentTransaction;
use App\Models\Transaction\Transaction;

// FIXME:
// class AgentTransactionService implements IBaseService
class AgentTransactionService
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
            $query->with(['originalAgent', 'device' => fn ($q) => $q->whereHas('person')->with(['device', 'person'])]);
        } else {
            $query->with(['device' => fn ($q) => $q->whereHas('person')->with(['device', 'person'])]);
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
            ->with(['originalAgent', 'device' => fn ($q) => $q->whereHas('person')->with(['device', 'person'])])
            ->whereHasMorph(
                'originalTransaction',
                [AgentTransaction::class],
                fn ($q) => $q->where('agent_id', $agentId)
            )
            ->whereHas('device', fn ($q) => $q->whereIn('device_serial', $customerDeviceSerials))
            ->latest()->paginate();
    }

    public function create(array $transactionData): AgentTransaction
    {
        return $this->agentTransaction->newQuery()->create($transactionData);
    }

    public function update($model, array $data): AgentTransaction
    {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete($model): ?bool
    {
        throw new \Exception('Method delete() not yet implemented.');
    }
}

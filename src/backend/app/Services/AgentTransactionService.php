<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Transaction\AgentTransaction;
use App\Models\Transaction\Transaction;
use App\Services\Interfaces\IAgentTransactionService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class AgentTransactionService implements IAgentTransactionService {
    public function __construct(
        private AgentTransaction $agentTransaction,
        private Transaction $transaction,
        private Device $device,
    ) {}

    /**
     * @return Collection<int, Transaction>|LengthAwarePaginator<int, Transaction>
     */
    public function getAll(
        ?int $limit = null,
        ?int $agentId = null,
        bool $forApp = false,
    ): Collection|LengthAwarePaginator {
        $query = $this->transaction->newQuery();

        if ($forApp) {
            $query->with(['originalTransaction', 'device' => fn ($q) => $q->whereHas('person')->with(['device', 'person'])]);
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

        return $limit ? $query->paginate($limit) : $query->get();
    }

    /**
     * @return Collection<int, Transaction>|LengthAwarePaginator<int, Transaction>
     */
    public function getByCustomerId(int $agentId, ?int $customerId = null): Collection|LengthAwarePaginator {
        $customerDeviceSerials = $this->device->newQuery()->where('person_id', $customerId)
            ->pluck('device_serial');

        if (!$customerDeviceSerials->count()) {
            return new Collection();
        }

        return $this->transaction->newQuery()
            ->with(['originalTransaction', 'device' => fn ($q) => $q->whereHas('person')->with(['device', 'person'])])
            ->whereHasMorph(
                'originalTransaction',
                [AgentTransaction::class],
                fn ($q) => $q->where('agent_id', $agentId)
            )
            ->whereHas('device', fn ($q) => $q->whereIn('device_serial', $customerDeviceSerials))
            ->latest()
            ->paginate();
    }

    public function getById(int $id): AgentTransaction {
        throw new \Exception('Method getById() not yet implemented.');
    }

    /**
     * @param array<string, mixed> $transactionData
     */
    public function create(array $transactionData): AgentTransaction {
        return $this->agentTransaction->newQuery()->create($transactionData);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(AgentTransaction $model, array $data): AgentTransaction {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete(AgentTransaction $model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }
}

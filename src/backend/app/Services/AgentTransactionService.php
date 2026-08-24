<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Device;
use App\Models\Transaction\AgentTransaction;
use App\Models\Transaction\Transaction;
use App\Services\Interfaces\IAgentTransactionService;
use Illuminate\Database\Eloquent\Builder;
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
            $query->with([
                'originalTransaction',
                'device' => fn ($q) => $q->whereHas('person')->with(['device', 'person']),
                'nonPaygoAppliance.person',
                'nonPaygoAppliance.appliance',
            ]);
        } else {
            $query->with(['device' => fn ($q) => $q->whereHas('person')->with(['device', 'person'])]);
        }

        $query->whereHasMorph(
            'originalTransaction',
            [AgentTransaction::class],
            static function ($q) use ($agentId) {
                $q->where('agent_id', $agentId);
            }
        )->latest()->orderByDesc('id');

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
            ->orderByDesc('id')
            ->paginate();
    }

    public function getById(int $id): AgentTransaction {
        throw new \Exception('Method getById() not yet implemented.');
    }

    /**
     * Find a transaction the given agent may read the token of, and eager-load
     * that token. A transaction qualifies when the agent recorded it, or when it
     * targeted a device of a customer in the agent's mini-grid — an installment
     * settled by an admin, by another agent or by a payment provider still has
     * to show its token in the field app. Returns null when the transaction
     * doesn't exist or falls outside both.
     */
    public function findForAgent(Agent $agent, int $transactionId): ?Transaction {
        return $this->transaction->newQuery()
            ->with(['token'])
            ->where('id', $transactionId)
            ->where(fn (Builder $query) => $query->whereHasMorph(
                'originalTransaction',
                [AgentTransaction::class],
                fn ($q) => $q->where('agent_id', $agent->id)
            )->orWhereHas(
                'device.person.addresses.city',
                fn ($q) => $q->where('mini_grid_id', $agent->mini_grid_id)
            ))
            ->first();
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

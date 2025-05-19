<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Transaction\AgentTransaction;
use App\Models\Transaction\Transaction;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @implements IBaseService<AgentTransaction>
 */
class AgentTransactionService implements IBaseService {
    public function __construct(
        private AgentTransaction $agentTransaction,
        private Transaction $transaction,
        private Device $device,
    ) {}

    public function getAll(
        ?int $limit = null,
        $agentId = null,
        $forApp = false,
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

        $transactions = $limit ? $query->paginate($limit) : $query->get();

        // For backwards compatibility with the Agent App we are artificially adding
        // the column `original_agent`.
        // TODO: Confirm the app is actually using `original_agent`
        // -> If yes, move to `original_transaction`
        // -> If no, remove this code
        $transactions->each(function ($transaction) {
            $transaction->setAttribute('original_agent', $transaction->originalTransaction);
        });

        return $transactions;
    }

    public function getByCustomerId(int $agentId, ?int $customerId = null): Collection|LengthAwarePaginator {
        $customerDeviceSerials = $this->device->newQuery()->where('person_id', $customerId)
            ->pluck('device_serial');

        if (!$customerDeviceSerials->count()) {
            return new Collection();
        }

        $transactions = $this->transaction->newQuery()
            ->with(['originalTransaction', 'device' => fn ($q) => $q->whereHas('person')->with(['device', 'person'])])
            ->whereHasMorph(
                'originalTransaction',
                [AgentTransaction::class],
                fn ($q) => $q->where('agent_id', $agentId)
            )
            ->whereHas('device', fn ($q) => $q->whereIn('device_serial', $customerDeviceSerials))
            ->latest()
            ->paginate();

        // For backwards compatibility with the Agent App we are artificially adding
        // the column `original_agent`.
        // TODO: Confirm the app is actually using `original_agent`
        // -> If yes, move to `original_transaction`
        // -> If no, remove this code
        $transactions->each(function ($transaction) {
            $transaction->setAttribute('original_agent', $transaction->originalTransaction);
        });

        return $transactions;
    }

    public function getById(int $id): AgentTransaction {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function create(array $transactionData): AgentTransaction {
        return $this->agentTransaction->newQuery()->create($transactionData);
    }

    public function update($model, array $data): AgentTransaction {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }
}

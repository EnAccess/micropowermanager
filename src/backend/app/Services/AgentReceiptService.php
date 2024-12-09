<?php

namespace App\Services;

use App\Models\AgentReceipt;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @implements IBaseService<AgentReceipt>
 */
class AgentReceiptService implements IBaseService {
    public function __construct(
        private AgentReceipt $agentReceipt,
    ) {}

    public function getAll(?int $limit = null, ?int $agentId = null): Collection|LengthAwarePaginator {
        $query = $this->agentReceipt->newQuery()
            ->with(['user', 'agent', 'history']);

        if ($agentId) {
            $query->whereHas(
                'agent',
                function ($q) use ($agentId) {
                    $q->where('agent_id', $agentId);
                }
            );
        }
        if ($limit) {
            return $query->latest()->paginate($limit);
        } else {
            return $query->latest()->paginate();
        }
    }

    public function create(array $receiptData): AgentReceipt {
        return $this->agentReceipt->newQuery()->create($receiptData);
    }

    public function getById(int $id): AgentReceipt {
        throw new \Exception('Method getById() not yet implemented.');
    }

    public function update($model, array $data): AgentReceipt {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }

    public function getLastReceipt($agentId) {
        return $this->agentReceipt->newQuery()->where('agent_id', $agentId)
            ->latest('id')->first();
    }

    public function getLastReceiptDate($agent) {
        $lastReceiptDate = $this->agentReceipt->newQuery()->where('agent_id', $agent->id)
            ->lastest('created_at')
            ->first();

        if ($lastReceiptDate) {
            return $lastReceiptDate->created_at;
        }

        return $agent->created_at;
    }
}

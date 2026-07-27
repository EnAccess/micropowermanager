<?php

namespace App\Services;

use App\Models\AgentReceipt;
use App\Services\Interfaces\IBaseService;
use App\Traits\HasCrudOperations;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @implements IBaseService<AgentReceipt>
 */
class AgentReceiptService implements IBaseService {
    /** @use HasCrudOperations<AgentReceipt> */
    use HasCrudOperations;

    public function __construct(
        private AgentReceipt $agentReceipt,
    ) {}

    protected function crudModel(): AgentReceipt {
        return $this->agentReceipt;
    }

    /**
     * @return Collection<int, AgentReceipt>|LengthAwarePaginator<int, AgentReceipt>
     */
    public function getAll(?int $limit = null, ?int $agentId = null): Collection|LengthAwarePaginator {
        $query = $this->agentReceipt->newQuery()
            ->with(['user', 'agent', 'history', 'detail']);

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
        }

        return $query->latest()->paginate();
    }

    public function getLastReceipt(int $agentId): ?AgentReceipt {
        return $this->agentReceipt->newQuery()
            ->where('agent_id', $agentId)
            ->latest('id')
            ->first();
    }

    /**
     * @param object{id: int, created_at: Carbon} $agent
     */
    public function getLastReceiptDate($agent): Carbon {
        $lastReceiptDate = $this->agentReceipt->newQuery()
            ->where('agent_id', $agent->id)
            ->latest('created_at')
            ->first();

        if ($lastReceiptDate) {
            return $lastReceiptDate->created_at;
        }

        return $agent->created_at;
    }
}

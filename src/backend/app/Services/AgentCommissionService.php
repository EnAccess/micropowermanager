<?php

namespace App\Services;

use App\Models\AgentCommission;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @implements IBaseService<AgentCommission>
 */
class AgentCommissionService implements IBaseService {
    public function __construct(
        private AgentCommission $agentCommission,
    ) {}

    public function create(array $agentCommissiondata): AgentCommission {
        return $this->agentCommission->newQuery()->create($agentCommissiondata);
    }

    public function update($agentCommission, array $agentCommissiondata): AgentCommission {
        $agentCommission->update($agentCommissiondata);
        $agentCommission->fresh();

        return $agentCommission;
    }

    public function delete($agentCommission): ?bool {
        return $agentCommission->delete();
    }

    public function getById(int $id): AgentCommission {
        return $this->agentCommission->newQuery()->find($id);
    }

    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        if ($limit) {
            return $this->agentCommission->newQuery()->paginate($limit);
        }

        return $this->agentCommission->newQuery()->get();
    }
}

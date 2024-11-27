<?php

namespace App\Services;

use App\Models\AgentCharge;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;

/**
 * @implements IBaseService<AgentCharge>
 */
class AgentChargeService implements IBaseService {
    public function __construct(
        private AgentCharge $agentCharge,
    ) {}

    public function create(array $agentChargeData): AgentCharge {
        return $this->agentCharge->newQuery()->create($agentChargeData);
    }

    public function getById(int $id): AgentCharge {
        throw new \Exception('Method getById() not yet implemented.');
    }

    public function update($model, array $data): AgentCharge {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }

    public function getAll(?int $limit = null): Collection {
        throw new \Exception('Method getAll() not yet implemented.');
    }
}

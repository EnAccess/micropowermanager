<?php

namespace App\Services;

use App\Models\AgentCharge;

class AgentChargeService implements IBaseService
{
    public function __construct(
        private AgentCharge $agentCharge
    ) {
    }

    public function create(array $agentChargeData): AgentCharge
    {
        return $this->agentCharge->newQuery()->create($agentChargeData);
    }

    public function getById(int $id): AgentCharge
    {
        throw new \Exception('Method getById() not yet implemented.');
    }

    public function update($model, array $data): AgentCharge
    {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete($model): ?bool
    {
        throw new \Exception('Method delete() not yet implemented.');
    }

    public function getAll($limit = null)
    {
        // TODO: Implement getAll() method.
    }
}

<?php

namespace App\Services;

use App\Models\AgentCharge;

class AgentChargeService implements IBaseService
{
    public function __construct(private AgentCharge $agentCharge)
    {
    }

    public function create($agentChargeData)
    {
        return $this->agentCharge->newQuery()->create($agentChargeData);
    }

    public function getById($id)
    {
        // TODO: Implement getById() method.
    }

    public function update($model, $data)
    {
        // TODO: Implement update() method.
    }

    public function delete($model)
    {
        // TODO: Implement delete() method.
    }

    public function getAll($limit = null)
    {
        // TODO: Implement getAll() method.
    }
}

<?php

namespace App\Services;

use App\Models\AgentCommission;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AgentCommissionService implements IBaseService
{
    public function __construct(
        private AgentCommission $agentCommission
    ) {
    }

    /**
     * @return Model|Builder
     */
    public function create(array $agentCommissiondata): AgentCommission
    {
        return $this->agentCommission->newQuery()->create($agentCommissiondata);
    }

    /**
     * @param       $agentCommission
     * @param array $data
     */
    public function update($agentCommission, array $agentCommissiondata): AgentCommission
    {
        $agentCommission->update($agentCommissiondata);
        $agentCommission->fresh();

        return $agentCommission;
    }

    public function delete($agentCommission): ?bool
    {
        return $agentCommission->delete();
    }

    public function getById(int $id): AgentCommission
    {
        return $this->agentCommission->newQuery()->find($id);
    }

    public function getAll($limit = null)
    {
        if ($limit) {
            return $this->agentCommission->newQuery()->paginate($limit);
        }

        return $this->agentCommission->newQuery()->get();
    }
}

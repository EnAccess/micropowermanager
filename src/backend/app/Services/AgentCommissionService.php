<?php

namespace App\Services;

use App\Models\AgentCommission;
use App\Services\Interfaces\IBaseService;
use App\Traits\HasCrudOperations;

/**
 * @implements IBaseService<AgentCommission>
 */
class AgentCommissionService implements IBaseService {
    /** @use HasCrudOperations<AgentCommission> */
    use HasCrudOperations;

    public function __construct(
        private AgentCommission $agentCommission,
    ) {}

    protected function crudModel(): AgentCommission {
        return $this->agentCommission;
    }
}

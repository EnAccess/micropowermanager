<?php

namespace App\Services;

use App\Models\AgentCharge;
use App\Services\Interfaces\IBaseService;
use App\Traits\HasCrudOperations;

/**
 * @implements IBaseService<AgentCharge>
 */
class AgentChargeService implements IBaseService {
    /** @use HasCrudOperations<AgentCharge> */
    use HasCrudOperations;

    public function __construct(
        private AgentCharge $agentCharge,
    ) {}

    protected function crudModel(): AgentCharge {
        return $this->agentCharge;
    }
}

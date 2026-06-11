<?php

namespace App\Services;

use App\Models\AgentReceiptDetail;
use App\Services\Interfaces\IBaseService;
use App\Traits\HasCrudOperations;

/**
 * @implements IBaseService<AgentReceiptDetail>
 */
class AgentReceiptDetailService implements IBaseService {
    /** @use HasCrudOperations<AgentReceiptDetail> */
    use HasCrudOperations;

    public function __construct(
        private AgentReceiptDetail $agentReceiptDetail,
    ) {}

    protected function crudModel(): AgentReceiptDetail {
        return $this->agentReceiptDetail;
    }

    public function getSummary(int $agentId): mixed {
        return $this->agentReceiptDetail->newQuery()->select('summary')
            ->whereHas(
                'receipt',
                static function ($q) use ($agentId) {
                    $q->where('agent_id', $agentId);
                }
            )->latest()->firstOrFail()->summary;
    }

}

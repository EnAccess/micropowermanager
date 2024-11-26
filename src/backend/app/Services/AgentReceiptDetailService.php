<?php

namespace App\Services;

use App\Models\AgentReceiptDetail;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;

/**
 * @implements IBaseService<AgentReceiptDetail>
 */
class AgentReceiptDetailService implements IBaseService {
    public function __construct(
        private AgentReceiptDetail $agentReceiptDetail,
    ) {}

    public function getSummary($agentId) {
        return $this->agentReceiptDetail->newQuery()->select('summary')
            ->whereHas(
                'receipt',
                static function ($q) use ($agentId) {
                    $q->where('agent_id', $agentId);
                }
            )->latest()->firstOrFail()->summary;
    }

    public function getById(int $id): AgentReceiptDetail {
        throw new \Exception('Method getById() not yet implemented.');
    }

    public function create(array $agentReceiptDetailData): AgentReceiptDetail {
        return $this->agentReceiptDetail->create($agentReceiptDetailData);
    }

    public function update($model, array $data): AgentReceiptDetail {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }

    public function getAll(?int $limit = null): Collection {
        throw new \Exception('Method getAll() not yet implemented.');
    }
}

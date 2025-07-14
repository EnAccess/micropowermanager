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

    public function getSummary(int $agentId): mixed {
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

    /**
     * @param array<string, mixed> $agentReceiptDetailData
     */
    public function create(array $agentReceiptDetailData): AgentReceiptDetail {
        return $this->agentReceiptDetail->create($agentReceiptDetailData);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update($model, array $data): AgentReceiptDetail {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }

    /**
     * @return Collection<int, AgentReceiptDetail>
     */
    public function getAll(?int $limit = null): Collection {
        throw new \Exception('Method getAll() not yet implemented.');
    }
}

<?php

namespace App\Services;

use App\Models\AgentReceipt;
use App\Models\AgentReceiptDetail;

class AgentReceiptDetailService implements IBaseService
{
    public function __construct(private AgentReceiptDetail $agentReceiptDetail)
    {
    }

    public function getSummary($agentId)
    {
        return $this->agentReceiptDetail->newQuery()->select('summary')
            ->whereHas(
                'receipt',
                static function ($q) use ($agentId) {
                    $q->where('agent_id', $agentId);
                }
            )->latest()->firstOrFail()->summary;
    }
    public function getById($id)
    {
        // TODO: Implement getById() method.
    }

    public function create($agentReceiptDetailData)
    {
        return $this->agentReceiptDetail->create($agentReceiptDetailData);
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

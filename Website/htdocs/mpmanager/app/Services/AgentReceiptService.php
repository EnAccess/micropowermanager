<?php

namespace App\Services;

use App\Models\AgentReceipt;

class AgentReceiptService implements IBaseService
{
    public function __construct(private AgentReceipt $agentReceipt)
    {
    }

    public function getAll($limit = null, $agentId = null)
    {
        $query = $this->agentReceipt->newQuery()
            ->with(['user', 'agent', 'history']);

        if ($agentId) {
            $query->whereHas(
                'agent',
                function ($q) use ($agentId) {
                    $q->where('agent_id', $agentId);
                }
            );
        }
        if ($limit) {
            return $query->latest()->paginate($limit);
        } else {
            return $query->latest()->paginate();
        }
    }

    public function create($receiptData)
    {
        return $this->agentReceipt->newQuery()->create($receiptData);
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

    public function getLastReceipt($agentId)
    {
        return $this->agentReceipt->newQuery()->where('agent_id', $agentId)
            ->latest('id')->first();
    }

    public function getLastReceiptDate($agent)
    {
        $lastReceiptDate = $this->agentReceipt->newQuery()->where('agent_id', $agent->id)->get()->last();

        if ($lastReceiptDate) {
            return $lastReceiptDate->created_at;
        }

        return $agent->created_at;
    }
}

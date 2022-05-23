<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentAssignedAppliances;
use App\Models\AgentBalanceHistory;
use App\Models\AgentCharge;
use App\Models\AgentCommission;
use App\Models\AgentReceipt;
use App\Models\Transaction\AgentTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use phpDocumentor\Reflection\Types\This;

class AgentBalanceHistoryService extends BaseService implements IBaseService, IAssociative
{

    public function __construct(private AgentBalanceHistory $agentBalanceHistory)
    {
        parent::__construct([$agentBalanceHistory]);
    }

    public function getAll($limit = null, $agentId = null)
    {
        $query = $this->agentBalanceHistory->newQuery()
            ->whereHasMorph(
            'trigger',
            '*'
        );
        if($agentId) {
            $query->where('agent_id', $agentId);
        }
        if ($limit) {

            return $query->latest()->paginate($limit);

        }
        return  $query->latest()->get();
    }

    public function getLastAgentBalanceHistory($agentId)
    {
        return $this->agentBalanceHistory->newQuery()->where('agent_id', $agentId)->get()->last();
    }

    public function getTotalAmountSinceLastVisit($agentBalanceHistoryId,$agentId)
    {
        return $this->agentBalanceHistory->newQuery()->where('agent_id', $agentId)
        ->where('id', '>', $agentBalanceHistoryId)
        ->whereIn('trigger_type', ['agent_appliance', 'agent_transaction'])
        ->sum('amount');
    }

    public function create($agentBalanceHistoryData)
    {
        return $this->agentBalanceHistory->newQuery()->create($agentBalanceHistoryData);
    }

    public function make($agentBalanceHistoryData)
    {
        return $this->agentBalanceHistory->newQuery()->make($agentBalanceHistoryData);
    }

    public function save($agentBalanceHistory)
    {
        $agentBalanceHistory->save();
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


}

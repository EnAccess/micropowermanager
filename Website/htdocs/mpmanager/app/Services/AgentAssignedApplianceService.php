<?php

namespace App\Services;

use App\Models\AgentAssignedAppliances;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AgentAssignedApplianceService implements IAgentRelatedService
{

    /**
     * @param $agentId
     * @return LengthAwarePaginator
     */
    public function list($agentId): LengthAwarePaginator
    {
        return AgentAssignedAppliances::with(['user', 'agent', 'applianceType'])
            ->whereHas(
                'agent',
                function ($q) use ($agentId) {
                    $q->where('agent_id', $agentId);
                }
            )
            ->latest()->paginate();
    }

    /**
     * @param array $applianceData
     * @return Builder|Model
     */
    public function create(array $applianceData)
    {

        return AgentAssignedAppliances::query()->create([
            'agent_id' => $applianceData['agent_id'],
            'user_id' => $applianceData['user_id'],
            'appliance_type_id' => $applianceData['appliance_type_id'],
            'cost' => $applianceData['cost'],
        ]);
    }
}

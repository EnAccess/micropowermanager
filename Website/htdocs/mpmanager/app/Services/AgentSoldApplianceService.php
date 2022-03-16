<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentSoldAppliance;
use App\Models\AssetPerson;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AgentSoldApplianceService implements IAgentRelatedService
{

    /**
     * @return LengthAwarePaginator
     */
    public function list($agentId)
    {
        return AssetPerson::with('person', 'assetType', 'rates')
            ->whereHasMorph(
                'creator',
                [Agent::class],
                function ($q) use ($agentId) {
                    $q->where('id', $agentId);
                }
            )->latest()
            ->paginate();
    }

    public function customerSoldList($customerId, $agentId): LengthAwarePaginator
    {
        return AssetPerson::with('person', 'assetType', 'rates')
            ->whereHasMorph(
                'creator',
                [Agent::class],
                function ($q) use ($agentId) {
                    $q->where('id', $agentId);
                }
            )
            ->where('person_id', $customerId)
            ->latest()
            ->paginate();
    }

    /**
     * @return Builder|Model
     */
    public function create(array $applianceData)
    {
        return AgentSoldAppliance::query()->create(
            [

                'person_id' => $applianceData['person_id'],
                'agent_assigned_appliance_id' => $applianceData['agent_assigned_appliance_id'],
            ]
        );
    }

    public function listForWeb(int $agentId): LengthAwarePaginator
    {

        return AgentSoldAppliance::with(
            [
                'assignedAppliance',
                'assignedAppliance.applianceType',
                'person',

            ]
        )->whereHas(
            'assignedAppliance',
            function ($q) use ($agentId) {
                $q->whereHas(
                    'agent',
                    function ($q) use ($agentId) {
                        $q->where('agent_id', $agentId);
                    }
                );
            }
        )->latest()->paginate();
    }
}

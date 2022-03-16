<?php

namespace App\Services;

use App\Models\AgentCommission;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AgentCommissionService
{


    /**
     * @return Model|Builder
     */
    public function create()
    {
        return AgentCommission::query()->create(
            request()->only(
                'name',
                'energy_commission',
                'appliance_commission',
                'risk_balance'
            )
        );
    }

    /**
     * @param AgentCommission $agentCommission
     * @param array $data
     * @return mixed
     */
    public function update(AgentCommission $agentCommission, array $data)
    {
        $agentCommission->update([
            'name' => $data['name'],
            'energy_commission' => $data['energy_commission'],
            'appliance_commission' => $data['appliance_commission'],
            'risk_balance' => $data['risk_balance']
        ]);

        return $agentCommission->fresh();
    }

    /**
     * @param AgentCommission $agentCommission
     * @throws Exception
     */
    public function delete(AgentCommission $agentCommission): void
    {
        $agentCommission->delete();
    }
}

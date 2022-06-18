<?php

namespace App\Services;

use App\Models\AgentAssignedAppliances;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AgentAssignedApplianceService  implements IBaseService
{

    public function __construct(private AgentAssignedAppliances $agentAssignedAppliance)
    {
    }


    public function create($applianceData)
    {

        return $this->agentAssignedAppliance->newQuery()->create([
            'agent_id' => $applianceData['agent_id'],
            'user_id' => $applianceData['user_id'],
            'appliance_type_id' => $applianceData['appliance_type_id'],
            'cost' => $applianceData['cost'],
        ]);
    }

    public function getById($id)
    {
        return $this->agentAssignedAppliance->newQuery()->with('applianceType')->find($id);
    }

    public function update($model, $data)
    {
        // TODO: Implement update() method.
    }

    public function delete($model)
    {
        // TODO: Implement delete() method.
    }

    public function getAll($limit = null, $agentId = null,)
    {
        $query = $this->agentAssignedAppliance->newQuery();

        if ($agentId){
            $query->with(['user', 'agent', 'applianceType'])
                ->whereHas(
                    'agent',
                    function ($q) use ($agentId) {
                        $q->where('agent_id', $agentId);
                    }
                );
        }

        if ($limit){

            return $query->latest()->paginate($limit);
        }

        return $query->latest()->get();
    }
}

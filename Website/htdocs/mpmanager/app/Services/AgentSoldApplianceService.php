<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentSoldAppliance;
use App\Models\AssetPerson;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use phpDocumentor\Reflection\Types\This;

class AgentSoldApplianceService extends BaseService implements IBaseService
{

    public function __construct(private AgentSoldAppliance $agentSoldAppliance)
    {
        parent::__construct([$agentSoldAppliance]);
    }

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
    public function create($applianceData)
    {
        return AgentSoldAppliance::query()->create(
            [

                'person_id' => $applianceData['person_id'],
                'agent_assigned_appliance_id' => $applianceData['agent_assigned_appliance_id'],
            ]
        );
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

    public function getAll($limit = null, $agentId = null, $customerId = null)
    {

        $query = $this->agentSoldAppliance->newQuery()->with([
            'assignedAppliance',
            'assignedAppliance.applianceType',
            'person'
        ]);

        if ($agentId) {
            $query->whereHas(
                'assignedAppliance',
                function ($q) use ($agentId) {
                    $q->whereHas(
                        'agent',
                        function ($q) use ($agentId) {
                            $q->where('agent_id', $agentId);
                        }
                    );
                }
            );
        }
        if ($customerId) {
            $query->where('person_id', $customerId);
        }
        if ($limit) {
            return $query->latest()->paginate($limit);
        } else {
            return $query->latest()->paginate();
        }

    }
}

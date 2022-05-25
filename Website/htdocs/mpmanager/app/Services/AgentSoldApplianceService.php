<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentSoldAppliance;
use App\Models\AssetPerson;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use phpDocumentor\Reflection\Types\This;
use function Symfony\Component\String\s;

class AgentSoldApplianceService extends BaseService implements IBaseService
{

    public function __construct(
        private AgentSoldAppliance $agentSoldAppliance,
        private AssetPerson $assetPerson
    ) {
        parent::__construct([$agentSoldAppliance]);
    }


    public function create($applianceData)
    {
        return $this->agentSoldAppliance->newQuery()->create($applianceData);
    }


    public function getById($agentId, $customerId = null)
    {
        return $this->assetPerson->newQuery()->with(['person', 'assetType', 'rates'])
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

    public function update($model, $data)
    {
        // TODO: Implement update() method.
    }

    public function delete($model)
    {
        // TODO: Implement delete() method.
    }


    public function getAll($limit = null, $agentId = null, $customerId = null, $forApp = false)
    {
        if ($forApp) {
            return $this->list($agentId);
        }

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

    public function list($agentId)
    {

        return $this->assetPerson->newQuery()->with(['person', 'assetType', 'rates'])
            ->whereHasMorph(
                'creator',
                [Agent::class],
                function ($q) use ($agentId) {
                    $q->where('id', $agentId);
                }
            )->latest()
            ->paginate();
    }
}

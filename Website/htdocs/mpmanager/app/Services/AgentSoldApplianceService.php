<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentSoldAppliance;
use App\Models\AssetPerson;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @implements IBaseService<AgentSoldAppliance>
 */
class AgentSoldApplianceService implements IBaseService
{
    public function __construct(
        private AgentSoldAppliance $agentSoldAppliance,
        private AssetPerson $assetPerson
    ) {
    }

    public function create($data): AgentSoldAppliance
    {
        /** @var AgentSoldAppliance $result */
        $result = $this->agentSoldAppliance->newQuery()->create($data);

        return $result;
    }

    public function getById(int $id, ?int $customerId = null): AgentSoldAppliance
    {
        /** @var AgentSoldAppliance $result */
        $result = $this->assetPerson->newQuery()->with(['person', 'device', 'rates'])
            ->whereHasMorph(
                'creator',
                [Agent::class],
                function ($q) use ($id) {
                    $q->where('id', $id);
                }
            )
            ->where('person_id', $customerId)
            ->latest()
            ->firstOrFail();

        return $result;
    }



    public function update($model, array $data): AgentSoldAppliance
    {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete($model): ?bool
    {
        throw new \Exception('Method delete() not yet implemented.');
    }

    public function getAll(
        ?int $limit = null,
        $agentId = null,
        $customerId = null,
        $forApp = false
    ): Collection|LengthAwarePaginator {
        if ($forApp) {
            return $this->list($agentId);
        }

        $query = $this->agentSoldAppliance->newQuery()->with([
            'assignedAppliance',
            'assignedAppliance.appliance.assetType',
            'person',
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
        return $this->assetPerson->newQuery()->with(['person', 'device', 'rates'])
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

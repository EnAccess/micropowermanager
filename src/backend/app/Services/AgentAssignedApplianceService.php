<?php

namespace App\Services;

use App\Models\AgentAssignedAppliances;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @implements IBaseService<AgentAssignedAppliances>
 */
class AgentAssignedApplianceService implements IBaseService {
    public function __construct(
        private AgentAssignedAppliances $agentAssignedAppliance,
    ) {}

    /**
     * @param array<string, mixed> $applianceData
     */
    public function create(array $applianceData): AgentAssignedAppliances {
        return $this->agentAssignedAppliance->newQuery()->create([
            'agent_id' => $applianceData['agent_id'],
            'user_id' => $applianceData['user_id'],
            'appliance_id' => $applianceData['appliance_id'],
            'cost' => $applianceData['cost'],
        ]);
    }

    public function getById(int $id): ?AgentAssignedAppliances {
        return $this->agentAssignedAppliance->newQuery()->with('appliance')->find($id);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update($model, array $data): AgentAssignedAppliances {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }

    /**
     * @return Collection<int, AgentAssignedAppliances>|LengthAwarePaginator<AgentAssignedAppliances>
     */
    public function getAll(?int $limit = null, ?int $agentId = null): Collection|LengthAwarePaginator {
        $query = $this->agentAssignedAppliance->newQuery();

        if ($agentId) {
            $query->with(['user', 'agent', 'appliance'])
                ->whereHas(
                    'agent',
                    function ($q) use ($agentId) {
                        $q->where('agent_id', $agentId);
                    }
                );
        }

        if ($limit) {
            return $query->latest()->paginate($limit);
        }

        return $query->latest()->get();
    }
}

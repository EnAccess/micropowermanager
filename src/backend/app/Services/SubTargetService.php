<?php

namespace App\Services;

use App\Models\SubTarget;
use App\Services\Interfaces\IBaseService;
use App\Traits\HasCrudOperations;

/**
 * @implements IBaseService<SubTarget>
 */
class SubTargetService implements IBaseService {
    /** @use HasCrudOperations<SubTarget> */
    use HasCrudOperations;

    public function __construct(
        private SubTarget $subTarget,
    ) {}

    protected function crudModel(): SubTarget {
        return $this->subTarget;
    }

    /**
     * @param array{targetId: int, data: array<int, array{id: int, target: array{totalRevenue: float, newConnection: int, connectedPower: float, energyPerMonth: float, averageRevenuePerMonth: float}}>} $subTargetData
     */
    public function create(array $subTargetData): SubTarget {
        $targetId = $subTargetData['targetId'];

        foreach ($subTargetData['data'] as $data) {
            $subTarget = $this->subTarget->newQuery()->create([
                'target_id' => $targetId,
                'revenue' => $data['target']['totalRevenue'],
                'connection_id' => $data['id'],
                'new_connections' => $data['target']['newConnection'],
                'connected_power' => $data['target']['connectedPower'],
                'energy_per_month' => $data['target']['energyPerMonth'],
                'average_revenue_per_month' => $data['target']['averageRevenuePerMonth'],
            ]);
        }

        return $this->subTarget;
    }
}

<?php

namespace App\Services;

use App\Models\SubTarget;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;

/**
 * @implements IBaseService<SubTarget>
 */
class SubTargetService implements IBaseService {
    public function __construct(
        private SubTarget $subTarget,
    ) {}

    public function getById(int $id): SubTarget {
        throw new \Exception('Method getById() not yet implemented.');

        return new SubTarget();
    }

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

    public function update($model, array $data): SubTarget {
        throw new \Exception('Method update() not yet implemented.');

        return new SubTarget();
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }

    public function getAll(?int $limit = null): Collection {
        throw new \Exception('Method getAll() not yet implemented.');
    }
}

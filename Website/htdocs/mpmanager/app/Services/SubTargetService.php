<?php

namespace App\Services;

use App\Models\SubTarget;

class SubTargetService  implements IBaseService
{

    public function __construct(private SubTarget $subTarget)
    {

    }

    public function getById($id)
    {
        // TODO: Implement getById() method.
    }

    public function create($subTargetData)
    {
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
    }

    public function update($model, $data)
    {
        // TODO: Implement update() method.
    }

    public function delete($model)
    {
        // TODO: Implement delete() method.
    }

    public function getAll($limit = null)
    {
        // TODO: Implement getAll() method.
    }
}

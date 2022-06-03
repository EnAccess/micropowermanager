<?php

namespace App\Services;

use App\Models\Target;

class TargetService  implements IBaseService
{
    public function __construct(private Target $target)
    {

    }

    public function getById($targetId)
    {
        return $this->target->newQuery()->with(['subTargets', 'city'])->find($targetId);
    }

    public function create($targetData)
    {
        $target = $this->target->newQuery()->make([
            'data' => $targetData['data'],
            'target_date' => date('Y-m-d', strtotime($targetData['period'])),
            'type' => $targetData['targetType'],
        ]);
        $target->owner()->associate($targetData['owner']);
        $target->save();

        return $target;
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
        if ($limit) {

            return $this->target->newQuery()->with(['owner', 'subTargets.connectionType'])->orderBy(
                'target_date',
                'desc'
            )->paginate($limit);
        }

        return $this->target->newQuery()->with(['owner', 'subTargets.connectionType'])->orderBy(
            'target_date',
            'desc'
        )->get();
    }

    public function getTakenSlots($targetDate)
    {
        return $this->target->newQuery()->whereBetween('target_date',$targetDate)->get();
    }
}

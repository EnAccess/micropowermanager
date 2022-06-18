<?php

namespace App\Services;

use App\Models\TimeOfUsage;

class TimeOfUsageService  implements IBaseService
{
    public function __construct(private TimeOfUsage $timeOfUsage)
    {

    }

    public function create($timeOfUsageData)
    {
        return $this->timeOfUsage->newQuery()->create($timeOfUsageData);
    }

    public function getById($timeOfUsageId)
    {
        return $this->timeOfUsage->newQuery()->find($timeOfUsageId);
    }

    public function update($timeOfUsage, $timeOfUsageData)
    {
         $timeOfUsage->update($timeOfUsageData);
         $timeOfUsage->fresh();

         return $timeOfUsage;
    }

    public function delete($timeOfUsage)
    {
        return $timeOfUsage->delete();
    }


    public function getAll($limit = null)
    {
        // TODO: Implement getAll() method.
    }
}

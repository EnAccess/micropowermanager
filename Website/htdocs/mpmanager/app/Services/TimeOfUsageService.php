<?php

namespace App\Services;

use App\Models\TimeOfUsage;

class TimeOfUsageService extends BaseService
{
    public function __construct(private TimeOfUsage $timeOfUsage)
    {
        parent::__construct([$timeOfUsage]);
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
        return $timeOfUsage->update($timeOfUsageData);
    }

}
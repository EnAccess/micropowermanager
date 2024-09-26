<?php

namespace App\Services;

use App\Models\TimeOfUsage;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;

/**
 * @implements IBaseService<TimeOfUsage>
 */
class TimeOfUsageService implements IBaseService
{
    public function __construct(
        private TimeOfUsage $timeOfUsage
    ) {
    }

    public function create(array $timeOfUsageData): TimeOfUsage
    {
        return $this->timeOfUsage->newQuery()->create($timeOfUsageData);
    }

    public function getById(int $timeOfUsageId): TimeOfUsage
    {
        return $this->timeOfUsage->newQuery()->find($timeOfUsageId);
    }

    public function update($timeOfUsage, array $timeOfUsageData): TimeOfUsage
    {
        $timeOfUsage->update($timeOfUsageData);
        $timeOfUsage->fresh();

        return $timeOfUsage;
    }

    public function delete($timeOfUsage): ?bool
    {
        return $timeOfUsage->delete();
    }

    public function getAll(?int $limit = null): Collection
    {
        throw new \Exception('Method getAll() not yet implemented.');
    }
}

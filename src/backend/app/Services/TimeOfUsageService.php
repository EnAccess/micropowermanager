<?php

namespace App\Services;

use App\Models\TimeOfUsage;
use App\Services\Interfaces\IBaseService;
use App\Traits\HasCrudOperations;

/**
 * @implements IBaseService<TimeOfUsage>
 */
class TimeOfUsageService implements IBaseService {
    /** @use HasCrudOperations<TimeOfUsage> */
    use HasCrudOperations;

    public function __construct(
        private TimeOfUsage $timeOfUsage,
    ) {}

    protected function crudModel(): TimeOfUsage {
        return $this->timeOfUsage;
    }
}

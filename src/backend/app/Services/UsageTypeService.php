<?php

namespace App\Services;

use App\Models\UsageType;
use App\Services\Interfaces\IBaseService;
use App\Traits\HasCrudOperations;

/**
 * @implements IBaseService<UsageType>
 */
class UsageTypeService implements IBaseService {
    /** @use HasCrudOperations<UsageType> */
    use HasCrudOperations;

    public function __construct(
        private UsageType $usageType,
    ) {}

    protected function crudModel(): UsageType {
        return $this->usageType;
    }
}

<?php

namespace App\Services;

use App\Models\ConnectionGroup;
use App\Services\Interfaces\IBaseService;
use App\Traits\HasCrudOperations;

/**
 * @implements IBaseService<ConnectionGroup>
 */ class ConnectionGroupService implements IBaseService {
    /** @use HasCrudOperations<ConnectionGroup> */
    use HasCrudOperations;

    public function __construct(
        private ConnectionGroup $connectionGroup,
    ) {}

    protected function crudModel(): ConnectionGroup {
        return $this->connectionGroup;
    }

    public function getById(int $connectionGroupId): ConnectionGroup {
        return $this->connectionGroup->newQuery()->findOrFail($connectionGroupId);
    }
}

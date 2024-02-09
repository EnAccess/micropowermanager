<?php

namespace App\Services;

use App\Models\UsageType;

class UsageTypeService implements IBaseService
{
    public function __construct(private UsageType $usageType)
    {
    }

    public function getAll($limit = null)
    {
        return $this->usageType->newQuery()->get();
    }

    public function getById($id)
    {
        // TODO: Implement getById() method.
    }

    public function create($data)
    {
        // TODO: Implement create() method.
    }

    public function update($model, $data)
    {
        // TODO: Implement update() method.
    }

    public function delete($model)
    {
        // TODO: Implement delete() method.
    }
}

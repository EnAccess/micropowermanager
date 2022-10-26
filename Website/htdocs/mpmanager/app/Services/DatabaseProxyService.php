<?php

namespace App\Services;

use App\Models\DatabaseProxy;

class DatabaseProxyService implements IBaseService
{
    public function __construct(private DatabaseProxy $databaseProxy)
    {
    }

    public function getById($id)
    {
        // TODO: Implement getById() method.
    }

    public function create($databaseProxyData)
    {
        return $this->databaseProxy->newQuery()->create($databaseProxyData);
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

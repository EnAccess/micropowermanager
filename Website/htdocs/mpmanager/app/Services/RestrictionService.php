<?php

namespace App\Services;

use App\Models\Restriction;

class RestrictionService extends BaseService implements IBaseService
{
    public function __construct(private Restriction $restriction)
    {
        parent::__construct([$this->restriction]);
    }

    public function getRestrictionForTarget($target)
    {
        return $this->restriction->newQuery()->where('target', $target)->firstOrFail();
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

    public function getAll($limit = null)
    {
        // TODO: Implement getAll() method.
    }
}
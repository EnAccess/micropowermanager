<?php

namespace App\Services;

use App\Models\Restriction;

class RestrictionService implements IBaseService
{
    public function __construct(
        private Restriction $restriction
    ) {
    }

    public function getRestrictionForTarget($target)
    {
        return $this->restriction->newQuery()->where('target', $target)->firstOrFail();
    }

    public function getById(int $id): Restriction
    {
        throw new \Exception('Method getById() not yet implemented.');
    }

    public function create(array $data): Restriction
    {
        throw new \Exception('Method create() not yet implemented.');
    }

    public function update($model, array $data): Restriction
    {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete($model): ?bool
    {
        throw new \Exception('Method delete() not yet implemented.');
    }

    public function getAll($limit = null)
    {
        // TODO: Implement getAll() method.
    }
}

<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

/**
 * @template T of Model
 */
interface IBaseService
{
    /**
     * @return T
     */
    public function getById(int $id): Model;

    /**
     * @return T
     */
    public function create(array $data): Model;

    /**
     * @param T $model
     */
    public function update(Model $model, array $data): Model;

    /**
     * @param T $model
     */
    public function delete(Model $model): ?bool;

    public function getAll(?int $limit = null);
}

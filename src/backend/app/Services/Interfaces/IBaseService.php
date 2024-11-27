<?php

namespace App\Services\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface that defines basic CRUD operations. Use for all services that interact with entities.
 *
 * @template T of Model
 */
interface IBaseService {
    /** @return T|null */
    public function getById(int $id): ?Model;

    /** @return T */
    public function create(array $data): Model;

    /**
     * @param T $model
     *
     * @return T
     */
    public function update(Model $model, array $data): Model;

    /** @param T $model */
    public function delete(Model $model): ?bool;

    /** @return array<array-key, T>|Collection<array-key, T>|LengthAwarePaginator */
    public function getAll(?int $limit = null): Collection|LengthAwarePaginator;
}

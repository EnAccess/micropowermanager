<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Default CRUD implementations for services satisfying App\Services\Interfaces\IBaseService.
 *
 * Consuming classes point the trait at the entity to operate on via crudModel().
 *
 * @template T of Model
 */
trait HasCrudOperations {
    /**
     * Returns the model instance the CRUD operations run against.
     *
     * Implement this in the consuming service to return its own (usually
     * constructor-promoted) model property, e.g. `return $this->city;`. This is
     * the single place that binds the trait to a concrete model, which lets the
     * generic T resolve to that model in every method below.
     *
     * @return T
     */
    abstract protected function crudModel(): Model;

    /**
     * @return T|null
     */
    public function getById(int $id): ?Model {
        return $this->crudModel()->newQuery()->find($id);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return T
     */
    public function create(array $data): Model {
        return $this->crudModel()->newQuery()->create($data);
    }

    /**
     * @param T                    $model
     * @param array<string, mixed> $data
     *
     * @return T
     */
    public function update(Model $model, array $data): Model {
        $model->update($data);
        $model->fresh();

        return $model;
    }

    /**
     * @param T $model
     */
    public function delete(Model $model): ?bool {
        return $model->delete();
    }

    /**
     * @return Collection<int, T>|LengthAwarePaginator<int, T>
     */
    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        $query = $this->crudModel()->newQuery();

        if ($limit) {
            /** @var LengthAwarePaginator<int, T> $paginated */
            $paginated = $query->paginate($limit);

            return $paginated;
        }

        /** @var Collection<int, T> $models */
        $models = $query->get();

        return $models;
    }
}

<?php

namespace App\Services;

use App\Models\DatabaseProxy;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;

/**
 * @implements IBaseService<DatabaseProxy>
 */
class DatabaseProxyService implements IBaseService {
    public function __construct(private DatabaseProxy $databaseProxy) {}

    public function getById($id): DatabaseProxy {
        throw new \Exception('Method getById() not yet implemented.');
    }

    public function create(array $databaseProxyData): DatabaseProxy {
        return $this->databaseProxy->newQuery()->create($databaseProxyData);
    }

    public function update($model, array $data): DatabaseProxy {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }

    public function getAll(?int $limit = null): Collection {
        throw new \Exception('Method getAll() not yet implemented.');
    }
}

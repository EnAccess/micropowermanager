<?php

namespace App\Services;

use App\Models\DatabaseProxy;
use App\Services\Interfaces\IBaseService;
use App\Traits\HasCrudOperations;

/**
 * @implements IBaseService<DatabaseProxy>
 */
class DatabaseProxyService implements IBaseService {
    /** @use HasCrudOperations<DatabaseProxy> */
    use HasCrudOperations;

    public function __construct(private DatabaseProxy $databaseProxy) {}

    protected function crudModel(): DatabaseProxy {
        return $this->databaseProxy;
    }

    public function create(array $databaseProxyData): DatabaseProxy {
        return $this->databaseProxy->newQuery()->firstOrCreate($databaseProxyData);
    }

    public function deleteByEmail(string $email): void {
        $this->databaseProxy->newQuery()
            ->where(DatabaseProxy::COL_EMAIL, '=', $email)
            ->delete();
    }
}

<?php

namespace App\Services;

use App\Models\User;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;

/**
 * @implements IBaseService<DatabaseProxy>
 */
class DatabaseProxyService implements IBaseService {
    public function __construct(private User $user) {}

    public function getById($id): User {
        throw new \Exception('Method getById() not yet implemented.');

        return new User();
    }

    public function create(array $userData): User {
        // return $this->user->newQuery()->create($userData);
        throw new \Exception('Method create() should  not be used directly ');
    }

    public function update($model, array $data): User {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }

    public function getAll(?int $limit = null): Collection {
        throw new \Exception('Method getAll() not yet implemented.');
    }
}

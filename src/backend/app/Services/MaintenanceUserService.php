<?php

namespace App\Services;

use App\Models\MaintenanceUsers;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;

/**
 * @phpstan-type MaintenanceUserData array{
 *     name?: string,
 *     email?: string,
 *     phone?: string,
 *     role?: string,
 *     status?: string,
 *     created_at?: string,
 *     updated_at?: string
 * }
 */
class MaintenanceUserService implements IBaseService {
    public function __construct(
        private MaintenanceUsers $maintenanceUser,
    ) {}

    public function getMaintenanceUsersCount(): int {
        return $this->maintenanceUser->newQuery()->count();
    }

    /**
     * @param MaintenanceUserData $maintenanceUserData
     */
    public function create(array $maintenanceUserData): MaintenanceUsers {
        return $this->maintenanceUser->newQuery()->create($maintenanceUserData);
    }

    public function getById(int $id): MaintenanceUsers {
        return $this->maintenanceUser->newQuery()->find($id);
    }

    /**
     * @param MaintenanceUserData $data
     */
    public function update($model, array $data): MaintenanceUsers {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }

    public function getAll(?int $limit = null): Collection {
        throw new \Exception('Method getAll() not yet implemented.');
    }
}

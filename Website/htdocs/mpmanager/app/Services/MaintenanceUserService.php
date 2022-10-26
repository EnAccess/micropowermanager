<?php

namespace App\Services;

use App\Models\MaintenanceUsers;

class MaintenanceUserService implements IBaseService
{
    public function __construct(private MaintenanceUsers $maintenanceUser)
    {
    }

    public function getMaintenanceUsersCount()
    {
        return $this->maintenanceUser->newQuery()->count();
    }

    public function create($maintenanceUserData)
    {
        return $this->maintenanceUser->newQuery()->create($maintenanceUserData);
    }

    public function getById($id)
    {
        // TODO: Implement getById() method.
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

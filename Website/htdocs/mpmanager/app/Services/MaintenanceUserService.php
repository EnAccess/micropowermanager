<?php

namespace App\Services;

use App\Models\MaintenanceUsers;

class MaintenanceUserService extends BaseService
{

    public function __construct(private MaintenanceUsers $maintenanceUser)
    {
        parent::__construct([$maintenanceUser]);
    }

    public function createMaintenanceUser(int $personId,int $miniGridId)
    {
      return  $this->maintenanceUser->newQuery()->create([
            'person_id'=>$personId,
            'mini_grid_id'=>$miniGridId
        ]);
    }
}
<?php

namespace App\Services;

use App\Models\MaintenanceUsers;

class MaintenanceUserService
{

    public function __construct(private SessionService $sessionService,private MaintenanceUsers $maintenanceUser)
    {
        $this->sessionService->setModel($maintenanceUser);
    }

    public function createMaintenanceUser(int $personId,int $miniGridId)
    {
      return  $this->maintenanceUser->newQuery()->create([
            'person_id'=>$personId,
            'mini_grid_id'=>$miniGridId
        ]);
    }
}
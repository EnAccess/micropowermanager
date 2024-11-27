<?php

namespace App\Services;

use App\Models\AssetPerson;
use App\Models\User;
use App\Services\Interfaces\IAssignationService;

/**
 * @implements IAssignationService<AssetPerson, User>
 */
class UserAppliancePersonService implements IAssignationService {
    private AssetPerson $appliancePerson;
    private User $user;

    public function setAssigned($appliancePerson): void {
        $this->appliancePerson = $appliancePerson;
    }

    public function setAssignee($user): void {
        $this->user = $user;
    }

    public function assign(): AssetPerson {
        $this->appliancePerson->creator()->associate($this->user);

        return $this->appliancePerson;
    }
}

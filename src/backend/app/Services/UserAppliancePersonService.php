<?php

namespace App\Services;

use App\Models\AppliancePerson;
use App\Models\User;
use App\Services\Interfaces\IAssignationService;

/**
 * @implements IAssignationService<AppliancePerson, User>
 */
class UserAppliancePersonService implements IAssignationService {
    private AppliancePerson $appliancePerson;
    private User $user;

    public function setAssigned($appliancePerson): void {
        $this->appliancePerson = $appliancePerson;
    }

    public function setAssignee($user): void {
        $this->user = $user;
    }

    public function assign(): AppliancePerson {
        $this->appliancePerson->creator()->associate($this->user);

        return $this->appliancePerson;
    }
}

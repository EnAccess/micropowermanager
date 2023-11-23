<?php

namespace App\Services;

use App\Models\AssetPerson;
use App\Models\User;

class UserAppliancePersonService implements IAssignationService
{
    private User $user;
    private AssetPerson $appliancePerson;

    public function setAssigned($appliancePerson)
    {
        $this->appliancePerson = $appliancePerson;
    }

    public function setAssignee($user)
    {
        $this->user = $user;
    }

    public function assign()
    {
        $this->appliancePerson->creator()->associate($this->user);

        return $this->appliancePerson;
    }
}

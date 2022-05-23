<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AssetPerson;
use App\Models\AssetType;

class AgentAppliancePersonService implements IAssignationService
{
    private Agent $agent;
    private AssetPerson $appliancePerson;

    public function setAssigned($appliancePerson)
    {
        $this->appliancePerson = $appliancePerson;
    }

    public function setAssigner($agent)
    {
        $this->agent = $agent;
    }

    public function assign()
    {
        $this->appliancePerson->creator()->associate($this->agent);

        return $this->appliancePerson;
    }
}
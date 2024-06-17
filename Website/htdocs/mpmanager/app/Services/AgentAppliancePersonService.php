<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AssetPerson;

class AgentAppliancePersonService implements IAssignationService
{
    private Agent $agent;
    private AssetPerson $appliancePerson;

    public function setAssigned($appliancePerson)
    {
        $this->appliancePerson = $appliancePerson;
    }

    public function setAssignee($agent)
    {
        $this->agent = $agent;
    }

    public function assign()
    {
        $this->appliancePerson->creator()->associate($this->agent);

        return $this->appliancePerson;
    }
}

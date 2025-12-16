<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AppliancePerson;
use App\Services\Interfaces\IAssignationService;

/**
 * @implements IAssignationService<AppliancePerson, Agent>
 */
class AgentAppliancePersonService implements IAssignationService {
    private AppliancePerson $appliancePerson;
    private Agent $agent;

    public function setAssigned($appliancePerson): void {
        $this->appliancePerson = $appliancePerson;
    }

    public function setAssignee($agent): void {
        $this->agent = $agent;
    }

    public function assign(): AppliancePerson {
        $this->appliancePerson->creator()->associate($this->agent);

        return $this->appliancePerson;
    }
}

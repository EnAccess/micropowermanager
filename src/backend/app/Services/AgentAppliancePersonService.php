<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AssetPerson;
use App\Services\Interfaces\IAssignationService;

/**
 * @implements IAssignationService<AssetPerson, Agent>
 */
class AgentAppliancePersonService implements IAssignationService {
    private AssetPerson $appliancePerson;
    private Agent $agent;

    public function setAssigned($appliancePerson): void {
        $this->appliancePerson = $appliancePerson;
    }

    public function setAssignee($agent): void {
        $this->agent = $agent;
    }

    public function assign(): AssetPerson {
        $this->appliancePerson->creator()->associate($this->agent);

        return $this->appliancePerson;
    }
}

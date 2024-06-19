<?php

namespace App\Services;

use App\Models\GeographicalInformation;
use App\Models\MiniGrid;

class MiniGridGeographicalInformationService implements IAssignationService
{
    private MiniGrid $miniGrid;
    private GeographicalInformation $geographicInformation;

    public function setAssigned($assigned)
    {
        $this->geographicInformation = $assigned;
    }

    public function setAssignee($assignee)
    {
        $this->miniGrid = $assignee;
    }

    public function assign()
    {
        $this->geographicInformation->owner()->associate($this->miniGrid);

        return $this->geographicInformation;
    }
}

<?php

namespace App\Services;

use App\Models\GeographicalInformation;
use App\Models\MiniGrid;
use App\Services\Interfaces\IAssignationService;

/**
 * @implements IAssignationService<GeographicalInformation, MiniGrid>
 */
class MiniGridGeographicalInformationService implements IAssignationService {
    private GeographicalInformation $geographicInformation;
    private MiniGrid $miniGrid;

    public function setAssigned($assigned): void {
        $this->geographicInformation = $assigned;
    }

    public function setAssignee($assignee): void {
        $this->miniGrid = $assignee;
    }

    public function assign(): GeographicalInformation {
        $this->geographicInformation->owner()->associate($this->miniGrid);

        return $this->geographicInformation;
    }
}

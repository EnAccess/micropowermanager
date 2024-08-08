<?php

namespace App\Services\Interfaces;

use Illuminate\Database\Eloquent\Model;

/**
 * @template TAssigned of Model
 * @template TAssignee of Model
 */
interface IAssignationService
{
    /** @param TAssigned $assigned */
    public function setAssigned($assigned): void;

    /** @param TAssignee $assignee */
    public function setAssignee($assignee): void;

    /** @return TAssigned */
    public function assign(): Model;
}

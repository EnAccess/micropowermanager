<?php

namespace App\Services\Interfaces;

interface IAssignationService
{
    public function setAssigned($assigned);

    public function setAssignee($assignee);

    public function assign();
}

<?php

namespace App\Services;

interface IAssignationService
{
    public function setAssigned($assigned);

    public function setAssignee($assignee);

    public function assign();
}

<?php

namespace App\Services;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Model;

interface IAssignationService
{
    public function setAssigned($assigned);

    public function setAssignee($assignee);

    public function assign();
}

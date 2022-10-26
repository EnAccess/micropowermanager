<?php

namespace App\Services;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Model;

interface IAssignationService
{
    public function setAssigned($assigned);

    public function setAssigner($assigning);

    public function assign();
}

<?php

namespace App\Models;

use App\Models\Base\MasterModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UsageType extends MasterModel
{
    use HasFactory;

    protected $table = 'usage_types';
}

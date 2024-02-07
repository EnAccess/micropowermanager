<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsageType extends MasterModel
{
    use HasFactory;

    protected $table = 'usage_types';
}

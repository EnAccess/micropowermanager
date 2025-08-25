<?php

namespace App\Models;

use App\Models\Base\BaseModelCentral;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UsageType extends BaseModelCentral {
    use HasFactory;

    protected $table = 'usage_types';
}

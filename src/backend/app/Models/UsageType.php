<?php

namespace App\Models;

use App\Models\Base\BaseModelCore;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UsageType extends BaseModelCore {
    use HasFactory;

    protected $table = 'usage_types';
}

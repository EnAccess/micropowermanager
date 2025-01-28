<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Plugins extends BaseModel {
    use HasFactory;
    public const ACTIVE = 1;
    public const INACTIVE = 0;
}

<?php

namespace App\Models;

use App\Models\Base\BaseModel;

/**
 * @property int $status
 */
class Plugins extends BaseModel {
    public const ACTIVE = 1;
    public const INACTIVE = 0;
}

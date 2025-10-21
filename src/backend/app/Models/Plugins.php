<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property int         $mpm_plugin_id
 * @property bool        $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Plugins extends BaseModel {
    public const ACTIVE = 1;
    public const INACTIVE = 0;
}

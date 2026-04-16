<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    /** @return BelongsTo<MpmPlugin, $this> */
    public function mpmPlugin(): BelongsTo {
        return $this->belongsTo(MpmPlugin::class);
    }
}

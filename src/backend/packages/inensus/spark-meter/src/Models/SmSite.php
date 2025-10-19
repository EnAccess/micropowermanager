<?php

namespace Inensus\SparkMeter\Models;

use App\Models\MiniGrid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property MiniGrid $mpmMiniGrid
 */
class SmSite extends \App\Models\Base\BaseModel {
    protected $table = 'sm_sites';

    public function mpmMiniGrid(): BelongsTo {
        return $this->belongsTo(MiniGrid::class, 'mpm_mini_grid_id');
    }
}

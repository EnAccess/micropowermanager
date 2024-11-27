<?php

namespace Inensus\SparkMeter\Models;

use App\Models\MiniGrid;

class SmSite extends BaseModel {
    protected $table = 'sm_sites';

    public function mpmMiniGrid() {
        return $this->belongsTo(MiniGrid::class, 'mpm_mini_grid_id');
    }
}

<?php

namespace Inensus\SparkMeter\Models;

use App\Models\Base\BaseModel;
use App\Models\MiniGrid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property      int           $id
 * @property      string        $site_id
 * @property      int           $mpm_mini_grid_id
 * @property      string        $thundercloud_url
 * @property      string|null   $thundercloud_token
 * @property      bool          $is_authenticated
 * @property      bool          $is_online
 * @property      string|null   $hash
 * @property      Carbon|null   $created_at
 * @property      Carbon|null   $updated_at
 * @property-read MiniGrid|null $mpmMiniGrid
 */
class SmSite extends BaseModel {
    protected $table = 'sm_sites';

    public function mpmMiniGrid(): BelongsTo {
        return $this->belongsTo(MiniGrid::class, 'mpm_mini_grid_id');
    }
}

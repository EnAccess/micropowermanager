<?php

namespace Inensus\SteamaMeter\Models;

use App\Models\Base\BaseModel;
use App\Models\MiniGrid;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property      int                                         $id
 * @property      int                                         $site_id
 * @property      int                                         $mpm_mini_grid_id
 * @property      string|null                                 $hash
 * @property      Carbon|null                                 $created_at
 * @property      Carbon|null                                 $updated_at
 * @property-read Collection<int, SteamaAgent>                $agents
 * @property-read MiniGrid|null                               $mpmMiniGrid
 * @property-read Collection<int, SteamaSiteLevelPaymentPlan> $paymentPlans
 */
class SteamaSite extends BaseModel {
    protected $table = 'steama_sites';

    /**
     * @return BelongsTo<MiniGrid, $this>
     */
    public function mpmMiniGrid(): BelongsTo {
        return $this->belongsTo(MiniGrid::class, 'mpm_mini_grid_id');
    }

    /**
     * @return HasMany<SteamaSiteLevelPaymentPlan, $this>
     */
    public function paymentPlans(): HasMany {
        return $this->hasMany(SteamaSiteLevelPaymentPlan::class);
    }

    /**
     * @return HasMany<SteamaAgent, $this>
     */
    public function agents(): HasMany {
        return $this->hasMany(SteamaAgent::class);
    }
}

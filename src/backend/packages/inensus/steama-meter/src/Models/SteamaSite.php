<?php

namespace Inensus\SteamaMeter\Models;

use App\Models\MiniGrid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property MiniGrid                                    $mpmMiniGrid
 * @property Collection<int, SteamaSiteLevelPaymentPlan> $paymentPlans
 * @property Collection<int, SteamaAgent>                $agents
 */
class SteamaSite extends BaseModel {
    protected $table = 'steama_sites';

    public function mpmMiniGrid(): BelongsTo {
        return $this->belongsTo(MiniGrid::class, 'mpm_mini_grid_id');
    }

    public function paymentPlans(): HasMany {
        return $this->hasMany(SteamaSiteLevelPaymentPlan::class);
    }

    public function agents(): HasMany {
        return $this->hasMany(SteamaAgent::class);
    }
}

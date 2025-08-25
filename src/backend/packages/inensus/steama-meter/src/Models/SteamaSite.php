<?php

namespace Inensus\SteamaMeter\Models;

use App\Models\MiniGrid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

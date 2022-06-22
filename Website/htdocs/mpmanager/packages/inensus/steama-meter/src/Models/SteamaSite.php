<?php

namespace Inensus\SteamaMeter\Models;

use App\Models\MiniGrid;
use Illuminate\Database\Eloquent\Model;

class SteamaSite extends BaseModel
{
    protected $table = 'steama_sites';

    public function mpmMiniGrid()
    {
        return $this->belongsTo(MiniGrid::class, 'mpm_mini_grid_id');
    }
    public function paymentPlans()
    {
        return $this->hasMany(SteamaSiteLevelPaymentPlan::class);
    }
    public function agents()
    {
        return $this->hasMany(SteamaAgent::class);
    }
}

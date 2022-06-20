<?php

namespace App\Models;

use App\Models\SubTarget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;

/**
 * Class Target
 *
 * @package App\Models
 *
 * @property int $id
 * @property string $start_date
 * @property string $end_date
 * @property int $city_id
 */
class Target extends BaseModel
{

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }


    public function subTargets(): HasMany
    {
        return $this->hasMany(SubTarget::class);
    }


    /**
     * @param $cityId
     * @param string $endDate
     * @return Builder
     */
    public function targetForMiniGrid($cityId, string $endDate): Builder
    {
        return $this::with('subTargets.connectionType', 'city')
            ->where('owner_id', $cityId)
            ->where('owner_type', 'mini-grid')
            ->where('target_date', '>=', $endDate)
            ->orderBy('target_date')
            ->limit(1);
    }

    public function targetForCluster($miniGridIds, string $endDate)
    {
        return $this::select(DB::raw("*, YEARWEEK(target_date,3) as period"))
            ->with('subTargets.connectionType', 'city')
            ->whereIn('owner_id', $miniGridIds)
            ->where('owner_type', 'mini-grid')
            ->where('target_date', '>=', $endDate)
            ->orderBy('target_date', 'asc');
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function periodTargetAlternative($cityId, $startDate)
    {
        return $this::select(DB::raw("*, YEARWEEK(target_date,3) as period"))->with(
            'subTargets.connectionType',
            'city'
        )
            ->where('owner_id', $cityId)
            ->where('owner_type', 'mini-grid')
            ->where('target_date', '<', $startDate)
            ->orderBy('target_date', 'desc')->limit(1);
    }
}

<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;

/**
 * Class Target.
 *
 * @property int    $id
 * @property string $start_date
 * @property string $end_date
 * @property int    $city_id
 */
class Target extends BaseModel {
    /** @use HasFactory<\Database\Factories\TargetFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<City, $this>
     */
    public function city(): BelongsTo {
        return $this->belongsTo(City::class);
    }

    /**
     * @return HasMany<SubTarget, $this>
     */
    public function subTargets(): HasMany {
        return $this->hasMany(SubTarget::class);
    }

    /**
     * @param int|string $cityId
     * @param string     $endDate
     *
     * @return Builder<Target>
     */
    public function targetForMiniGrid(int|string $cityId, string $endDate): Builder {
        return $this::with('subTargets.connectionType', 'city')
            ->where('owner_id', $cityId)
            ->where('owner_type', 'mini-grid')
            ->where('target_date', '>=', $endDate)
            ->orderBy('target_date')
            ->limit(1);
    }

    /**
     * @param array<int|string> $miniGridIds
     * @param string            $endDate
     *
     * @return Builder<Target>
     */
    public function targetForCluster(array $miniGridIds, string $endDate): Builder {
        return $this::query()
            ->select(DB::raw('*, YEARWEEK(target_date,3) as period'))
            ->with('subTargets.connectionType', 'city')
            ->whereIn('owner_id', $miniGridIds)
            ->where('owner_type', 'mini-grid')
            ->where('target_date', '>=', $endDate)
            ->orderBy('target_date', 'asc');
    }

    /**
     * @return MorphTo<\Illuminate\Database\Eloquent\Model, $this>
     */
    public function owner(): MorphTo {
        return $this->morphTo();
    }

    /**
     * @param int|string $cityId
     * @param string     $startDate
     *
     * @return Builder<Target>
     */
    public function periodTargetAlternative(int|string $cityId, string $startDate): Builder {
        return $this::query()
            ->select(DB::raw('*, YEARWEEK(target_date,3) as period'))->with(
                'subTargets.connectionType',
                'city'
            )
            ->where('owner_id', $cityId)
            ->where('owner_type', 'mini-grid')
            ->where('target_date', '<', $startDate)
            ->orderBy('target_date', 'desc')->limit(1);
    }
}

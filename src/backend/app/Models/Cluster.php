<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Database\Factories\ClusterFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use MPM\Target\TargetAssignable;

/**
 * Class Cluster.
 *
 * @property      int                       $id
 * @property      string                    $name
 * @property      int                       $manager_id
 * @property      array<array-key, mixed>   $geo_data
 * @property      Carbon|null               $created_at
 * @property      Carbon|null               $updated_at
 * @property-read Collection<int, City>     $cities
 * @property-read User|null                 $manager
 * @property-read Collection<int, MiniGrid> $miniGrids
 *
 * Special attributes only used for caching services:
 * @property int   $population This field is used only for caching.
 * @property int   $meterCount This field is used only for caching.
 * @property float $revenue    This field is used only for caching.
 */
class Cluster extends BaseModel implements TargetAssignable {
    /** @use HasFactory<ClusterFactory> */
    use HasFactory;

    public const RELATION_NAME = 'cluster';

    /** @return BelongsTo<User, $this> */
    public function manager(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<City, $this> */
    public function cities(): HasMany {
        return $this->hasMany(City::class);
    }

    /** @return HasMany<MiniGrid, $this> */
    public function miniGrids(): HasMany {
        return $this->hasMany(MiniGrid::class);
    }

    protected function casts(): array {
        return [
            'geo_data' => 'array',
        ];
    }
}

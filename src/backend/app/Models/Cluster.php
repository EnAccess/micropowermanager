<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MPM\Target\TargetAssignable;

/**
 * Class Cluster.
 *
 * @property int    $id
 * @property string $name
 * @property int    $manager_id
 * @property string $geo_data
 * @property string $updated_at
 * @property string $created_at
 * @property int    $population
 * @property int    $meterCount
 * @property float  $revenue
 */
class Cluster extends BaseModel implements TargetAssignable {
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<static>> */
    use HasFactory;

    public const RELATION_NAME = 'cluster';

    protected $casts = [
        'geo_data' => 'array',
    ];

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
}

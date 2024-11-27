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
 * @property int manager_id
 * @property string geo_data
 * @property string $updated_at
 * @property string $created_at
 */
class Cluster extends BaseModel implements TargetAssignable {
    use HasFactory;

    public const RELATION_NAME = 'cluster';

    protected $casts = [
        'geo_data' => 'array',
    ];

    public function manager(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function cities(): HasMany {
        return $this->hasMany(City::class);
    }

    public function miniGrids(): HasMany {
        return $this->hasMany(MiniGrid::class);
    }
}

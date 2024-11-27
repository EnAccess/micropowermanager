<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Collection;
use MPM\Target\TargetAssignable;

/**
 * Class MiniGrid.
 *
 * @property int        $id
 * @property string     $name
 * @property int        $cluster_id
 * @property Collection $cities
 */
class MiniGrid extends BaseModel implements TargetAssignable {
    use HasFactory;

    public const RELATION_NAME = 'mini-grid';
    protected $guarded = [];

    public function cities(): HasMany {
        return $this->hasMany(City::class);
    }

    public function cluster(): BelongsTo {
        return $this->belongsTo(Cluster::class);
    }

    public function location(): MorphOne {
        return $this->morphOne(GeographicalInformation::class, 'owner');
    }

    public function agent(): HasOne {
        return $this->hasOne(Agent::class);
    }

    public function setClusterId(int $clusterId): void {
        $this->cluster_id = $clusterId;
    }

    public function setName(string $name): void {
        $this->name = $name;
    }

    public function getClusterId(): int {
        return $this->cluster_id;
    }
}

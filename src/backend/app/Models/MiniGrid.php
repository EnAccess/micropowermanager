<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use App\Services\Interfaces\ITargetAssignable;
use Database\Factories\MiniGridFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;

/**
 * Class MiniGrid.
 *
 * @property      int                          $id
 * @property      int                          $cluster_id
 * @property      string                       $name
 * @property      Carbon|null                  $created_at
 * @property      Carbon|null                  $updated_at
 * @property-read Collection<int, Agent>       $agents
 * @property-read Collection<int, City>        $cities
 * @property-read Cluster|null                 $cluster
 * @property-read GeographicalInformation|null $location
 */
class MiniGrid extends BaseModel implements ITargetAssignable {
    /** @use HasFactory<MiniGridFactory> */
    use HasFactory;

    public const RELATION_NAME = 'mini-grid';
    protected $guarded = [];

    /**
     * @return HasMany<City, $this>
     */
    public function cities(): HasMany {
        return $this->hasMany(City::class);
    }

    /**
     * @return BelongsTo<Cluster, $this>
     */
    public function cluster(): BelongsTo {
        return $this->belongsTo(Cluster::class);
    }

    /**
     * @return MorphOne<GeographicalInformation, $this>
     */
    public function location(): MorphOne {
        return $this->morphOne(GeographicalInformation::class, 'owner');
    }

    /**
     * @return HasMany<Agent, $this>
     */
    public function agents(): HasMany {
        return $this->hasMany(Agent::class);
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

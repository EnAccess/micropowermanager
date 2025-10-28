<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use App\Models\Transaction\Transaction;
use Database\Factories\MiniGridFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;
use MPM\Target\TargetAssignable;

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
 *
 * Special attributes only used for caching services:
 * @property array{data: float}           $soldEnergy   This field is used only for caching.
 * @property Collection<int, Transaction> $transactions This field is used only for caching.
 * @property array<string, mixed>         $period       This field is used only for caching.
 * @property array<string, mixed>         $tickets      This field is used only for caching.
 * @property array<string, mixed>         $revenueList  This field is used only for caching.
 */
class MiniGrid extends BaseModel implements TargetAssignable {
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

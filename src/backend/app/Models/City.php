<?php

namespace App\Models;

use App\Models\Address\Address;
use App\Models\Base\BaseModel;
use Database\Factories\CityFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;
use Znck\Eloquent\Relations\BelongsToThrough as RelationsBelongsToThrough;
use Znck\Eloquent\Traits\BelongsToThrough;

/**
 * Class City.
 *
 * @property      int                          $id
 * @property      string                       $name
 * @property      int                          $country_id
 * @property      int                          $cluster_id
 * @property      int                          $mini_grid_id
 * @property      Carbon|null                  $created_at
 * @property      Carbon|null                  $updated_at
 * @property-read Collection<int, Address>     $addresses
 * @property-read Cluster|null                 $cluster
 * @property-read Country|null                 $country
 * @property-read GeographicalInformation|null $geo
 * @property-read GeographicalInformation|null $location
 * @property-read MiniGrid|null                $miniGrid
 * @property-read Collection<int, Target>      $targets
 */
class City extends BaseModel {
    /** @use HasFactory<CityFactory> */
    use HasFactory;
    use BelongsToThrough;

    public const RELATION_NAME = 'city';

    /**
     * @return HasMany<Target, $this>
     */
    public function targets(): HasMany {
        return $this->hasMany(Target::class);
    }

    /**
     * @return HasMany<Address, $this>
     */
    public function addresses(): HasMany {
        return $this->hasMany(Address::class);
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo {
        return $this->belongsTo(Country::class);
    }

    /**
     * @return BelongsTo<MiniGrid, $this>
     */
    public function miniGrid(): BelongsTo {
        return $this->belongsTo(MiniGrid::class);
    }

    /**
     * @return RelationsBelongsToThrough<Cluster, MiniGrid, $this>
     */
    public function cluster(): RelationsBelongsToThrough {
        return $this->belongsToThrough(Cluster::class, MiniGrid::class);
    }

    /**
     * @return MorphOne<GeographicalInformation, $this>
     */
    public function location(): MorphOne {
        return $this->morphOne(GeographicalInformation::class, 'owner');
    }

    /**
     * @return MorphOne<GeographicalInformation, $this>
     */
    public function geo(): MorphOne {
        return $this->morphOne(GeographicalInformation::class, 'owner');
    }
}

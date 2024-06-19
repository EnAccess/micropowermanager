<?php

namespace App\Models;

use App\Models\Address\Address;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Class City.
 *
 * @property int    $id
 * @property string $name
 * @property int    $country_id
 * @property int    $cluster_id
 * @property int    $mini_grid_id
 */
class City extends BaseModel
{
    public const RELATION_NAME = 'city';

    public function targets(): HasMany
    {
        return $this->hasMany(Target::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function miniGrid(): BelongsTo
    {
        return $this->belongsTo(MiniGrid::class);
    }

    public function cluster(): BelongsTo
    {
        return $this->belongsTo(Cluster::class);
    }

    public function location(): MorphOne
    {
        return $this->morphOne(GeographicalInformation::class, 'owner');
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setCountryId(int $countryId): void
    {
        $this->country_id = $countryId;
    }

    public function setClusterId(int $clusterId): void
    {
        $this->cluster_id = $clusterId;
    }

    public function setMiniGridId(int $miniGridId): void
    {
        $this->mini_grid_id = $miniGridId;
    }

    public function getMiniGridId(): int
    {
        return $this->mini_grid_id;
    }

    public function geo(): MorphOne
    {
        return $this->morphOne(GeographicalInformation::class, 'owner');
    }
}

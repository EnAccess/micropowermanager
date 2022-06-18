<?php

namespace App\Models;

use App\Models\Address\Address;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Class City
 *
 * @package  App
 * @property integer id
 * @property string name
 */
class City extends BaseModel
{

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
}

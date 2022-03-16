<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Class MiniGrid
 *
 * @package App
 *
 * @property int $id
 * @property string $name
 * @property int $cluster_id
 * @property int $data_stream;
 */
class MiniGrid extends Model
{

    protected $guarded = [];


    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }


    public function cluster(): BelongsTo
    {
        return $this->belongsTo(Cluster::class);
    }

    public function location(): MorphOne
    {
        return $this->morphOne(GeographicalInformation::class, 'owner');
    }
    public function agent(): HasOne
    {
        return $this->hasOne(Agent::Class);
    }
}

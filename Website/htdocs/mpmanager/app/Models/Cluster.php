<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Cluster
 *
 * @package App\Models
 *
 * @property int $id
 * @property string $name
 * @property int manager_id
 * @property string geo_data
 * @property string $updated_at
 * @property string $created_at
 */
class Cluster extends BaseModel
{
    protected $connection = 'test_company_db';
    protected $casts = [
        'geo_data' => 'array'
    ];


    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    public function miniGrids(): HasMany
    {
        return $this->hasMany(MiniGrid::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Solar extends Model
{
    protected $fillable = [
        'mini_grid_id',
        'node_id',
        'device_id',
        'solar_reading',
        'time_stamp',
        'min',
        'max',
        'average',
        'duration',
        'readings',
        'starting_time',
        'ending_time',
        'end_time',
    ];


    public function weatherData(): HasOne
    {
        return $this->hasOne(WeatherData::class);
    }
}

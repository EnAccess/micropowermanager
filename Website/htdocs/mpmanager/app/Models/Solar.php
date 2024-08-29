<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Solar extends BaseModel
{
    public function weatherData(): HasOne
    {
        return $this->hasOne(WeatherData::class);
    }
}

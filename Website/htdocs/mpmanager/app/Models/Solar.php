<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Solar extends BaseModel
{
    protected $connection = 'test_company_db';

    public function weatherData(): HasOne
    {
        return $this->hasOne(WeatherData::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeatherData extends BaseModel
{
    protected $connection = 'test_company_db';
    public function solar(): BelongsTo
    {
        return $this->belongsTo(Solar::class);
    }
}

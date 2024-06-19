<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeatherData extends BaseModel
{
    public function solar(): BelongsTo
    {
        return $this->belongsTo(Solar::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CityCluster extends BaseModel
{
    public function cities(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function clusters(): BelongsTo
    {
        return $this->belongsTo(Cluster::class);
    }
}

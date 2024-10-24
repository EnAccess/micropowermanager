<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class SolarHomeSystem extends BaseModel
{
    use HasFactory;

    public const RELATION_NAME = 'solar_home_system';
    protected $table = 'solar_home_systems';

    public function device(): MorphOne
    {
        return $this->morphOne(Device::class, 'device');
    }

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    public function appliance(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id', 'id');
    }
}

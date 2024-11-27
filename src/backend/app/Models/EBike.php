<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class EBike extends BaseModel {
    public const RELATION_NAME = 'e_bike';
    protected $table = 'e_bikes';

    public function device(): MorphOne {
        return $this->morphOne(Device::class, 'device');
    }

    public function manufacturer(): BelongsTo {
        return $this->belongsTo(Manufacturer::class);
    }

    public function appliance(): BelongsTo {
        return $this->belongsTo(Asset::class, 'asset_id', 'id');
    }
}

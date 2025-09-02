<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @property Device       $device
 * @property Manufacturer $manufacturer
 */
class EBike extends BaseModel {
    public const RELATION_NAME = 'e_bike';
    protected $table = 'e_bikes';

    /**
     * @return MorphOne<Device, $this>
     */
    public function device(): MorphOne {
        return $this->morphOne(Device::class, 'device');
    }

    /**
     * @return BelongsTo<Manufacturer, $this>
     */
    public function manufacturer(): BelongsTo {
        return $this->belongsTo(Manufacturer::class);
    }

    /**
     * @return BelongsTo<Asset, $this>
     */
    public function appliance(): BelongsTo {
        return $this->belongsTo(Asset::class, 'asset_id', 'id');
    }
}

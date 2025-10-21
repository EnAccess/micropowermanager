<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;

/**
 * @property      int               $id
 * @property      string            $serial_number
 * @property      int               $asset_id
 * @property      int               $manufacturer_id
 * @property      string|null       $receive_time
 * @property      string|null       $lat
 * @property      string|null       $lng
 * @property      float|null        $speed
 * @property      float|null        $mileage
 * @property      string|null       $status
 * @property      string|null       $soh
 * @property      float|null        $battery_level
 * @property      float|null        $battery_voltage
 * @property      Carbon|null       $created_at
 * @property      Carbon|null       $updated_at
 * @property-read Asset|null        $appliance
 * @property-read Device|null       $device
 * @property-read Manufacturer|null $manufacturer
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

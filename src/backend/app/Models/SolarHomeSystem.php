<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Database\Factories\SolarHomeSystemFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;

/**
 * @property      int                    $id
 * @property      int                    $appliance_id
 * @property      string                 $serial_number
 * @property      int                    $manufacturer_id
 * @property      Carbon|null            $created_at
 * @property      Carbon|null            $updated_at
 * @property-read Appliance|null             $appliance
 * @property-read Device|null            $device
 * @property-read Manufacturer|null      $manufacturer
 * @property-read Collection<int, Token> $tokens
 */
class SolarHomeSystem extends BaseModel {
    /** @use HasFactory<SolarHomeSystemFactory> */
    use HasFactory;

    public const RELATION_NAME = 'solar_home_system';
    protected $table = 'solar_home_systems';

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
     * @return BelongsTo<Appliance, $this>
     */
    public function appliance(): BelongsTo {
        return $this->belongsTo(Appliance::class, 'appliance_id', 'id');
    }

    /**
     * Get all tokens associated with this SolarHomeSystem through the Device relationship.
     *
     * Relationship flow:
     * SolarHomeSystem (id) → Device (device_id) → Token (device_id)
     *
     * Example:
     * SolarHomeSystem(id: 1)
     *   ↓
     * Device(id: 1, device_id: 1, device_type: 'solar_home_system')
     *   ↓
     * Token(device_id: 1, token_type: 'time', token_unit: 'days')
     *
     * @return HasManyThrough<Token, Device, $this>
     */
    public function tokens(): HasManyThrough {
        return $this->hasManyThrough(
            Token::class,        // The final model we want to reach (Token)
            Device::class,       // The intermediate model (Device)
            'device_id',         // Foreign key on Device table that points to SolarHomeSystem.id
            'device_id',         // Foreign key on Token table that points to Device.id
            'id',               // Local key on SolarHomeSystem table
            'id'                // Local key on Device table
        )->where('device_type', 'solar_home_system'); // Ensure we only get SHS devices
    }
}

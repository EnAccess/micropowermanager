<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class SolarHomeSystem extends BaseModel {
    /** @use HasFactory<\Database\Factories\SolarHomeSystemFactory> */
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
     * @return BelongsTo<Asset, $this>
     */
    public function appliance(): BelongsTo {
        return $this->belongsTo(Asset::class, 'asset_id', 'id');
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

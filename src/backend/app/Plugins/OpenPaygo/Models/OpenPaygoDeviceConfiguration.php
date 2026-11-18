<?php

namespace App\Plugins\OpenPaygo\Models;

use App\Models\Base\BaseModel;
use App\Models\Device;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property int         $device_id
 * @property string      $secret_key_hex
 * @property int         $starting_code
 * @property int|null    $time_divider
 * @property int         $next_counter
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class OpenPaygoDeviceConfiguration extends BaseModel {
    protected $table = 'openpaygo_device_configurations';

    protected $hidden = ['secret_key_hex'];

    protected $casts = [
        'starting_code' => 'integer',
        'time_divider' => 'integer',
        'next_counter' => 'integer',
    ];

    /**
     * @return BelongsTo<Device, $this>
     */
    public function device(): BelongsTo {
        return $this->belongsTo(Device::class);
    }
}

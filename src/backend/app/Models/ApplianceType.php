<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property      int                        $id
 * @property      string                     $name
 * @property      bool                       $paygo_enabled
 * @property      Carbon|null                $created_at
 * @property      Carbon|null                $updated_at
 * @property-read Collection<int, Appliance> $appliance
 */
class ApplianceType extends BaseModel {
    public const APPLIANCE_TYPE_SHS = 1;
    public const APPLIANCE_TYPE_E_BIKE = 2;
    public const APPLIANCE_TYPE_ELECTRONICS = 3;
    public const APPLIANCE_TYPE_GOODS = 4;

    protected $casts = [
        'paygo_enabled' => 'boolean',
    ];

    /**
     * @return HasMany<Appliance, $this>
     */
    public function appliance(): HasMany {
        return $this->hasMany(Appliance::class);
    }
}

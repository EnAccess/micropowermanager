<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property      int                    $id
 * @property      string                 $name
 * @property      Carbon|null            $created_at
 * @property      Carbon|null            $updated_at
 * @property-read Collection<int, Asset> $asset
 */
class AssetType extends BaseModel {
    public const APPLIANCE_TYPE_SHS = 1;

    /**
     * @return HasMany<Asset, $this>
     */
    public function asset(): HasMany {
        return $this->hasMany(Asset::class);
    }
}

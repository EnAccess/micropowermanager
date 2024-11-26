<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetType extends BaseModel {
    public const APPLIANCE_TYPE_SHS = 1;

    public function asset(): HasMany {
        return $this->hasMany(Asset::class);
    }
}

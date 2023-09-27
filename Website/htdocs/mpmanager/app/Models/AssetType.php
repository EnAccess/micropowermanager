<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AssetType extends BaseModel
{

    const APPLIANCE_TYPE_SHS = 1;
    public function asset(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}

<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends BaseModel {
    public function getRouteKeyName(): string {
        return 'country_code';
    }

    public function cities(): HasMany {
        return $this->hasMany(City::class);
    }
}

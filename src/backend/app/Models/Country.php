<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Country Class.
 *
 * @property int    $id
 * @property string $country_code
 * @property string $country_name
 * **/
class Country extends BaseModel {
    use HasFactory;

    /**
     * @return string
     */
    public function getRouteKeyName(): string {
        return 'country_code';
    }

    public function cities(): HasMany {
        return $this->hasMany(City::class);
    }
}

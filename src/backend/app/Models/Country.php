<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Database\Factories\CountryFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Country Class.
 *
 * @property      int                   $id
 * @property      string                $country_code
 * @property      string                $country_name
 * @property      Carbon|null           $created_at
 * @property      Carbon|null           $updated_at
 * @property-read Collection<int, City> $cities
 */
class Country extends BaseModel {
    /** @use HasFactory<CountryFactory> */
    use HasFactory;

    public function getRouteKeyName(): string {
        return 'country_code';
    }

    /**
     * @return HasMany<City, $this>
     */
    public function cities(): HasMany {
        return $this->hasMany(City::class);
    }
}

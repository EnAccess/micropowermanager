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
 * @property-read Collection<int, Village> $villages
 */
class Country extends BaseModel {
    /** @use HasFactory<CountryFactory> */
    use HasFactory;

    public function getRouteKeyName(): string {
        return 'country_code';
    }

    /**
     * @return HasMany<Village, $this>
     */
    public function villages(): HasMany {
        return $this->hasMany(Village::class);
    }
}

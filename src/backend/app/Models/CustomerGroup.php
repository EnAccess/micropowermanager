<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use App\Models\Meter\MeterTariff;
use App\Models\Person\Person;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property      int                     $id
 * @property      int                     $tariff_id
 * @property      string                  $name
 * @property      Carbon|null             $created_at
 * @property      Carbon|null             $updated_at
 * @property-read Collection<int, Person> $customers
 * @property-read MeterTariff|null        $tariff
 */
class CustomerGroup extends BaseModel {
    /**
     * @return BelongsTo<MeterTariff, $this>
     */
    public function tariff(): BelongsTo {
        return $this->belongsTo(MeterTariff::class);
    }

    /**
     * @return HasMany<Person, $this>
     */
    public function customers(): HasMany {
        return $this->hasMany(Person::class);
    }
}

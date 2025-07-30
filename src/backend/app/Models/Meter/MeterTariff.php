<?php

namespace App\Models\Meter;

use App\Models\AccessRate\AccessRate;
use App\Models\Base\BaseModel;
use App\Models\CustomerGroup;
use App\Models\SocialTariff;
use App\Models\TariffPricingComponent;
use App\Models\TimeOfUsage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Tariff.
 *
 * @property string     $name
 * @property int        $price       (100 times the price. Allows to play with .00 decimals)
 * @property int        $total_price (100 times the price. Allows to play with .00 decimals)
 * @property string     $currency
 * @property int|null   $factor
 * @property AccessRate $accessRate
 */
class MeterTariff extends BaseModel {
    use SoftDeletes;

    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<static>> */
    use HasFactory;

    public const RELATION_NAME = 'meter_tariff';
    public const DEFAULT_FACTOR = 1; // for energy usage
    public const SHS_FACTOR = 2; // for shs usage

    protected $table = 'meter_tariffs';
    protected $guarded = [];

    /** @return HasMany<Meter, $this> */
    public function meters(): HasMany {
        return $this->hasMany(Meter::class, 'tariff_id');
    }

    /** @return HasMany<Meter, $this> */
    public function metersCount(): HasMany {
        return $this->meters()
            ->selectRaw('tariff_id, count(*) as aggregate')
            ->groupBy('tariff_id');
    }

    /** @return HasMany<CustomerGroup, $this> */
    public function customerGroups(): HasMany {
        return $this->hasMany(CustomerGroup::class, 'tariff_id');
    }

    /** @return HasOne<AccessRate, $this> */
    public function accessRate(): HasOne {
        return $this->hasOne(AccessRate::class, 'tariff_id');
    }

    /** @return MorphMany<TariffPricingComponent, $this> */
    public function pricingComponent(): MorphMany {
        return $this->morphMany(TariffPricingComponent::class, 'owner');
    }

    /** @return HasOne<SocialTariff, $this> */
    public function socialTariff(): HasOne {
        return $this->hasOne(SocialTariff::class, 'tariff_id');
    }

    /** @return HasMany<TimeOfUsage, $this> */
    public function tou(): HasMany {
        return $this->hasMany(TimeOfUsage::class, 'tariff_id');
    }
}

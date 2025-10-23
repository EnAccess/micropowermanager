<?php

namespace App\Models\Meter;

use App\Models\AccessRate\AccessRate;
use App\Models\Base\BaseModel;
use App\Models\CustomerGroup;
use App\Models\SocialTariff;
use App\Models\TariffPricingComponent;
use App\Models\TimeOfUsage;
use Database\Factories\Meter\MeterTariffFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class Tariff.
 *
 * @property      int                                     $id
 * @property      string                                  $name
 * @property      float                                   $price
 * @property      string                                  $currency
 * @property      int|null                                $factor
 * @property      Carbon|null                             $created_at
 * @property      Carbon|null                             $updated_at
 * @property      Carbon|null                             $deleted_at
 * @property      float|null                              $total_price
 * @property      float                                   $minimum_purchase_amount
 * @property-read AccessRate|null                         $accessRate
 * @property-read Collection<int, CustomerGroup>          $customerGroups
 * @property-read Collection<int, Meter>                  $meters
 * @property-read Collection<int, Meter>                  $metersCount
 * @property-read Collection<int, TariffPricingComponent> $pricingComponent
 * @property-read SocialTariff|null                       $socialTariff
 * @property-read Collection<int, TimeOfUsage>            $tou
 */
class MeterTariff extends BaseModel {
    use SoftDeletes;

    /** @use HasFactory<MeterTariffFactory> */
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

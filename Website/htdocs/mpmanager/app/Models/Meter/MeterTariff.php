<?php

namespace App\Models\Meter;

use App\Models\AccessRate\AccessRate;
use App\Models\BaseModel;
use App\Models\CustomerGroup;
use App\Models\SocialTariff;
use App\Models\TariffPricingComponent;
use App\Models\TimeOfUsage;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Tariff
 *
 * @package App
 *
 * @property string $name
 * @property int $price (100 times the price. Allows to play with .00 decimals)
 * @property int $total_price (100 times the price. Allows to play with .00 decimals)
 * @property string $currency
 * @property int|null $factor
 */
class MeterTariff extends BaseModel
{
    public const RELATION_NAME = 'meter_tariff';

    const DEFAULT_FACTOR = 1; //for energy usage
    const SHS_FACTOR = 2; //for shs usage
    use SoftDeletes;

    protected $table = 'meter_tariffs';
    protected $guarded = [];

    public function meterParameter(): HasMany
    {
        return $this->hasMany(MeterParameter::class, 'tariff_id');
    }

    public function meterParametersCount()
    {
        return $this->meterParameter()
            ->selectRaw('tariff_id, count(*) as aggregate')
            ->groupBy('tariff_id');
    }

    public function customerGroups(): HasMany
    {
        return $this->hasMany(CustomerGroup::class, 'tariff_id');
    }


    public function accessRate(): HasOne
    {
        return $this->hasOne(AccessRate::class, 'tariff_id');
    }

    public function pricingComponent(): MorphMany
    {
        return $this->morphMany(TariffPricingComponent::class, 'owner');
    }

    public function socialTariff(): HasOne
    {
        return $this->hasOne(SocialTariff::class, 'tariff_id');
    }
    public function tou(): HasMany
    {
        return $this->hasMany(TimeOfUsage::class, 'tariff_id');
    }
}

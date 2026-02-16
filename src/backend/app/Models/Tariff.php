<?php

namespace App\Models;

use App\Models\AccessRate\AccessRate;
use App\Models\Base\BaseModel;
use App\Models\Meter\Meter;
use Database\Factories\TariffFactory;
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
 * @property      float                                   $minimum_purchase_amount
 * @property-read float                                   $total_price
 * @property-read AccessRate|null                         $accessRate
 * @property-read Collection<int, Meter>                  $meters
 * @property-read Collection<int, Meter>                  $metersCount
 * @property-read Collection<int, TariffPricingComponent> $pricingComponent
 * @property-read SocialTariff|null                       $socialTariff
 * @property-read Collection<int, TimeOfUsage>            $tou
 */
class Tariff extends BaseModel {
    use SoftDeletes;

    /** @use HasFactory<TariffFactory> */
    use HasFactory;

    public const RELATION_NAME = 'tariff';
    public const DEFAULT_FACTOR = 1; // for energy usage
    public const SHS_FACTOR = 2; // for shs usage

    protected $appends = ['total_price'];

    /**
     * Generic tariffs table.
     *
     * @var string
     */
    protected $table = 'tariffs';

    /**
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * @return HasMany<Meter, $this>
     */
    public function meters(): HasMany {
        return $this->hasMany(Meter::class, 'tariff_id');
    }

    /**
     * @return HasMany<Meter, $this>
     */
    public function metersCount(): HasMany {
        return $this->meters()
            ->selectRaw('tariff_id, count(*) as aggregate')
            ->groupBy('tariff_id');
    }

    /**
     * @return HasOne<AccessRate, $this>
     */
    public function accessRate(): HasOne {
        return $this->hasOne(AccessRate::class, 'tariff_id');
    }

    /**
     * @return MorphMany<TariffPricingComponent, $this>
     */
    public function pricingComponent(): MorphMany {
        return $this->morphMany(TariffPricingComponent::class, 'owner');
    }

    /**
     * @return HasOne<SocialTariff, $this>
     */
    public function socialTariff(): HasOne {
        return $this->hasOne(SocialTariff::class, 'tariff_id');
    }

    /**
     * @return HasMany<TimeOfUsage, $this>
     */
    public function tou(): HasMany {
        return $this->hasMany(TimeOfUsage::class, 'tariff_id');
    }

    /**
     * Computed total price: base kWh price plus all additional pricing components.
     */
    protected function getTotalPriceAttribute(): float {
        $base = (float) $this->price;

        $componentsTotal = $this->pricingComponent->sum(static fn (TariffPricingComponent $component): float => (float) $component->price);

        return $base + $componentsTotal;
    }
}

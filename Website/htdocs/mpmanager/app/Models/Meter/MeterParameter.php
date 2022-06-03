<?php

namespace App\Models\Meter;

use App\Models\AccessRate\AccessRate;
use App\Models\Address\Address;
use App\Models\BaseModel;
use App\Models\ConnectionGroup;
use App\Models\GeographicalInformation;
use App\Models\SocialTariffPiggyBank;
use App\Models\SubConnectionType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Class MeterParameter
 *
 * @package  App
 * @property string owner_type
 * @property int owner_id
 * @property int meter_id
 * @property int tariff_id
 * @property int connection_type_id
 */
class MeterParameter extends BaseModel
{

    protected $hidden = ['owner_id', 'owner_type', 'meter_id', 'tariff_id'];
    protected $guarded = [];

    public function connectionType(): BelongsTo
    {
        return $this->belongsTo(SubConnectionType::class, 'connection_type_id', 'id');
    }

    public function tariff(): BelongsTo
    {
        return $this->belongsTo(MeterTariff::class);
    }


    public function tariffAccessRate(): ?AccessRate
    {
        return $this->tariff->accessRate;
    }

    public function meter(): BelongsTo
    {
        return $this->belongsTo(Meter::class);
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function geo(): MorphOne
    {
        return $this->morphOne(GeographicalInformation::class, 'owner');
    }

    /**
     * @return string[]
     *
     * @psalm-return non-empty-list<string>
     */
    public function latLon(): array
    {
        return explode(',', $this->geo()->first()->points ?? '0.0,0.0');
    }

    //the address where the meter is been placed
    public function address(): MorphOne
    {
        return $this->morphOne(Address::class, 'owner');
    }

    public function connectionGroup(): BelongsTo
    {
        return $this->belongsTo(ConnectionGroup::class);
    }


    public function socialTariffPiggyBank(): HasOne
    {
        return $this->hasOne(SocialTariffPiggyBank::class);
    }
}

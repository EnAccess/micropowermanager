<?php

namespace App\Models\Meter;

use App\Models\AccessRate\AccessRate;
use App\Models\AccessRate\AccessRatePayment;
use App\Models\Base\BaseModel;
use App\Models\ConnectionGroup;
use App\Models\ConnectionType;
use App\Models\Device;
use App\Models\Manufacturer;
use App\Models\Token;
use App\Models\Transaction\Transaction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @property int         $id
 * @property MeterTariff $tariff
 * @property bool        $in_use
 */
class Meter extends BaseModel {
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<static>> */
    use HasFactory;

    public const RELATION_NAME = 'meter';
    protected $guarded = [];

    /** @var array<string, string> */
    public static $rules = [
        'serial_number' => 'required|min:1|unique:meters',
        'meter_type_id' => 'exists:tenant.meter_types,id',
        'manufacturer_id' => 'exists:tenant.manufacturers,id',
    ];

    /** @return BelongsTo<MeterType, $this> */
    public function meterType(): BelongsTo {
        return $this->belongsTo(MeterType::class);
    }

    /** @return MorphOne<Device, $this> */
    public function device(): MorphOne {
        return $this->morphOne(Device::class, 'device');
    }

    /** @return BelongsTo<Manufacturer, $this> */
    public function manufacturer(): BelongsTo {
        return $this->belongsTo(Manufacturer::class);
    }

    /** @return BelongsTo<MeterTariff, $this> */
    public function tariff(): BelongsTo {
        return $this->belongsTo(MeterTariff::class);
    }

    /** @return BelongsTo<ConnectionType, $this> */
    public function connectionType(): BelongsTo {
        return $this->belongsTo(ConnectionType::class, 'connection_type_id', 'id');
    }

    /** @return BelongsTo<ConnectionGroup, $this> */
    public function connectionGroup(): BelongsTo {
        return $this->belongsTo(ConnectionGroup::class);
    }

    /** @return HasOne<AccessRatePayment, $this> */
    public function accessRatePayment(): HasOne {
        return $this->hasOne(AccessRatePayment::class);
    }

    public function accessRate(): AccessRate {
        return $this->tariff->accessRate;
    }

    /** @return HasManyThrough<Token, Device, $this> */
    public function tokens(): HasManyThrough {
        return $this->hasManyThrough(
            Token::class,
            Device::class,
            'device_id',
            'device_id',
            'id',
            'id'
        )->where('device_type', 'meter');
    }

    /** @return HasMany<MeterConsumption, $this> */
    public function consumptions(): HasMany {
        return $this->hasMany(MeterConsumption::class);
    }

    /** @return HasMany<Transaction, $this> */
    public function transactions(): HasMany {
        return $this->hasMany(Transaction::class, 'message', 'serial_number');
    }

    public function findBySerialNumber(string $meterSerialNumber): ?self {
        /** @var Meter|null $result */
        $result = $this->newQuery()->where('serial_number', '=', $meterSerialNumber)->first();

        return $result;
    }

    public function getId(): int {
        return $this->id;
    }
}

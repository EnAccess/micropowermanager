<?php

namespace App\Models;

use App\Models\Address\Address;
use App\Models\Base\BaseModel;
use App\Models\Person\Person;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int            $connection_group_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property Device         $device
 * @property Manufacturer   $manufacturer
 */
class Device extends BaseModel {
    /** @use HasFactory<\Database\Factories\DeviceFactory> */
    use HasFactory;

    public const RELATION_NAME = 'device';

    // TODO: This name seems unintuive and confusing.
    // The device table now has a column called `id` and a column called `device_id`
    // but they are completely different things.
    // `id` is this device's... well... id, which it can be referenced with in the `device` table
    // `device_id` is the `id` in the target table depending on type. For example `meter` or `solar_home_system`.
    /**
     * @return MorphTo<\Illuminate\Database\Eloquent\Model, $this>
     */
    public function device(): MorphTo {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<Person, $this>
     */
    public function person(): BelongsTo {
        return $this->belongsTo(Person::class);
    }

    /**
     * @return MorphOne<Address, $this>
     */
    public function address(): MorphOne {
        return $this->morphOne(Address::class, 'owner');
    }

    /**
     * @return HasMany<Token, $this>
     */
    public function tokens(): HasMany {
        return $this->hasMany(Token::class, 'device_id', 'id');
    }

    /**
     * @return HasOne<AssetPerson, $this>
     */
    public function assetPerson(): HasOne {
        return $this->hasOne(AssetPerson::class, 'device_serial', 'device_serial');
    }

    /**
     * @return HasOneThrough<Asset, AssetPerson, $this>
     */
    public function appliance(): HasOneThrough {
        return $this->hasOneThrough(
            Asset::class,       // Final model we want (Asset)
            AssetPerson::class, // Intermediate model (AssetPerson)
            'device_serial',    // Foreign key on AssetPerson table that points to Device.device_serial
            'id',              // Foreign key on Asset table that points to AssetPerson.asset_id
            'device_serial',    // Local key on Device table
            'asset_id'         // Local key on AssetPerson table
        );
    }
}

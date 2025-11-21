<?php

namespace App\Models;

use App\Models\Address\Address;
use App\Models\Base\BaseModel;
use App\Models\Meter\Meter;
use App\Models\Person\Person;
use App\Models\Transaction\Transaction;
use Database\Factories\DeviceFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property      int                          $id
 * @property      int|null                     $person_id
 * @property      string                       $device_type
 * @property      int                          $device_id
 * @property      string                       $device_serial
 * @property      Carbon|null                  $created_at
 * @property      Carbon|null                  $updated_at
 * @property-read Address|null                 $address
 * @property-read Appliance|null                   $appliance
 * @property-read AppliancePerson|null             $appliancePerson
 * @property-read Meter|SolarHomeSystem|EBike  $device
 * @property-read Person|null                  $person
 * @property-read Collection<int, Token>       $tokens
 * @property-read Collection<int, Transaction> $transactions
 */
class Device extends BaseModel {
    /** @use HasFactory<DeviceFactory> */
    use HasFactory;

    public const RELATION_NAME = 'device';

    // TODO: This name seems unintuive and confusing.
    // The device table now has a column called `id` and a column called `device_id`
    // but they are completely different things.
    // `id` is this device's... well... id, which it can be referenced with in the `device` table
    // `device_id` is the `id` in the target table depending on type. For example `meter` or `solar_home_system`.
    /**
     * @return MorphTo<Meter|SolarHomeSystem|EBike, $this>
     */
    public function device(): MorphTo {
        // https://github.com/larastan/larastan/issues/1223
        // @phpstan-ignore return.type
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
     * @return HasOne<AppliancePerson, $this>
     */
    public function appliancePerson(): HasOne {
        return $this->hasOne(AppliancePerson::class, 'device_serial', 'device_serial');
    }

    /**
     * @return HasOneThrough<Appliance, AppliancePerson, $this>
     */
    public function appliance(): HasOneThrough {
        return $this->hasOneThrough(
            Appliance::class,
            AppliancePerson::class,
            'device_serial',
            'id',
            'device_serial',
            'appliance_id'
        );
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany {
        return $this->hasMany(Transaction::class, 'message', 'device_serial');
    }
}

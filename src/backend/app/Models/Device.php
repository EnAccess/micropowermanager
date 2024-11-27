<?php

namespace App\Models;

use App\Models\Address\Address;
use App\Models\Base\BaseModel;
use App\Models\Person\Person;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Device extends BaseModel {
    use HasFactory;

    public const RELATION_NAME = 'device';

    // TODO: This name seems unintuive and confusing.
    // The device table now has a column called `id` and a column called `device_id`
    // but they are completely different things.
    // `id` is this device's... well... id, which it can be references with in the `device` table
    // `device_id` is the `id` in the target table depending on type. For example `meter` or `solar_home_system`.
    public function device(): MorphTo {
        return $this->morphTo();
    }

    public function person(): BelongsTo {
        return $this->belongsTo(Person::class);
    }

    public function address(): MorphOne {
        return $this->morphOne(Address::class, 'owner');
    }

    public function appliance(): HasOne {
        return $this->hasOne(Asset::class, 'device_serial', 'device_serial');
    }
}

<?php

namespace App\Models;

use App\Models\Address\Address;
use App\Models\Person\Person;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Device extends BaseModel
{
    public const RELATION_NAME = 'device';

    public function device(): MorphTo
    {
        return $this->morphTo();
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function address(): MorphOne
    {
        return $this->morphOne(Address::class, 'owner');
    }

    public function appliance(): HasOne
    {
        return $this->hasOne(Asset::class, 'device_serial', 'device_serial');
    }
}

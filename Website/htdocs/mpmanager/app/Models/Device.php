<?php

namespace App\Models;

use App\Models\Address\Address;
use App\Models\Person\Person;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Device extends BaseModel
{

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

    public function geo(): MorphOne
    {
        return $this->morphOne(GeographicalInformation::class, 'owner');
    }
}

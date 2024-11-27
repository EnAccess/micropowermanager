<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use App\Models\Meter\MeterTariff;
use App\Models\Person\Person;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerGroup extends BaseModel {
    public function tariff(): BelongsTo {
        return $this->belongsTo(MeterTariff::class);
    }

    public function customers(): HasMany {
        return $this->hasMany(Person::class);
    }
}

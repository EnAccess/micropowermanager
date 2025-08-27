<?php

namespace Inensus\KelinMeter\Models;

use App\Models\Base\BaseModel;
use App\Models\Person\Person;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KelinCustomer extends BaseModel {
    protected $table = 'kelin_customers';

    public function mpmPerson(): BelongsTo {
        return $this->belongsTo(Person::class, 'mpm_customer_id');
    }

    public function kelinMeters(): HasMany {
        return $this->hasMany(KelinMeter::class, 'customer_no', 'customer_no');
    }
}

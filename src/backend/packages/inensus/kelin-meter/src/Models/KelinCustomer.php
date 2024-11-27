<?php

namespace Inensus\KelinMeter\Models;

use App\Models\Base\BaseModel;
use App\Models\Person\Person;

class KelinCustomer extends BaseModel {
    protected $table = 'kelin_customers';

    public function mpmPerson() {
        return $this->belongsTo(Person::class, 'mpm_customer_id');
    }

    public function kelinMeters() {
        return $this->hasMany(KelinMeter::class, 'customer_no', 'customer_no');
    }
}

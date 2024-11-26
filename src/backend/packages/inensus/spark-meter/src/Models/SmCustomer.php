<?php

namespace Inensus\SparkMeter\Models;

use App\Models\Person\Person;

class SmCustomer extends BaseModel {
    protected $table = 'sm_customers';

    public function mpmPerson() {
        return $this->belongsTo(Person::class, 'mpm_customer_id');
    }

    public function site() {
        return $this->belongsTo(SmSite::class, 'site_id', 'site_id');
    }
}

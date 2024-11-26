<?php

namespace Inensus\SteamaMeter\Models;

use App\Models\Person\Person;

class SteamaCustomer extends BaseModel {
    protected $table = 'steama_customers';

    public function mpmPerson() {
        return $this->belongsTo(Person::class, 'mpm_customer_id');
    }

    public function site() {
        return $this->belongsTo(SteamaSite::class, 'site_id', 'site_id');
    }

    public function userType() {
        return $this->belongsTo(SteamaUserType::class, 'user_type_id');
    }

    public function paymentPlans() {
        return $this->hasOne(SteamaCustomerBasisPaymentPlan::class);
    }

    public function stmMeters() {
        return $this->hasMany(SteamaMeter::class, 'customer_id', 'customer_id');
    }
}

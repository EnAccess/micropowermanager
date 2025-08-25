<?php

namespace Inensus\SteamaMeter\Models;

use App\Models\Person\Person;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SteamaCustomer extends BaseModel {
    protected $table = 'steama_customers';

    public function mpmPerson(): BelongsTo {
        return $this->belongsTo(Person::class, 'mpm_customer_id');
    }

    public function site(): BelongsTo {
        return $this->belongsTo(SteamaSite::class, 'site_id', 'site_id');
    }

    public function userType(): BelongsTo {
        return $this->belongsTo(SteamaUserType::class, 'user_type_id');
    }

    public function paymentPlans(): HasOne {
        return $this->hasOne(SteamaCustomerBasisPaymentPlan::class);
    }

    public function stmMeters(): HasMany {
        return $this->hasMany(SteamaMeter::class, 'customer_id', 'customer_id');
    }
}

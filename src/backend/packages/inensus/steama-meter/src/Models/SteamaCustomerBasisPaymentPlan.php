<?php

namespace Inensus\SteamaMeter\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SteamaCustomerBasisPaymentPlan extends BaseModel {
    protected $table = 'steama_customer_basis_payment_plans';

    public function customer(): BelongsTo {
        return $this->belongsTo(SteamaCustomer::class, 'customer_id');
    }

    public function paymentPlan(): MorphTo {
        return $this->morphTo();
    }
}

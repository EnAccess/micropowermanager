<?php

namespace Inensus\SteamaMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property      int                 $id
 * @property      int                 $customer_id
 * @property      string              $payment_plan_type
 * @property      int                 $payment_plan_id
 * @property      Carbon|null         $created_at
 * @property      Carbon|null         $updated_at
 * @property-read SteamaCustomer|null $customer
 * @property-read Model|\Eloquent     $paymentPlan
 */
class SteamaCustomerBasisPaymentPlan extends BaseModel {
    protected $table = 'steama_customer_basis_payment_plans';

    public function customer(): BelongsTo {
        return $this->belongsTo(SteamaCustomer::class, 'customer_id');
    }

    public function paymentPlan(): MorphTo {
        return $this->morphTo();
    }
}

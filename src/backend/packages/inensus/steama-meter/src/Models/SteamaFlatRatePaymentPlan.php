<?php

namespace Inensus\SteamaMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property float       $energy_price
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SteamaFlatRatePaymentPlan extends BaseModel {
    protected $table = 'steama_flat_rate_payment_plans';
}

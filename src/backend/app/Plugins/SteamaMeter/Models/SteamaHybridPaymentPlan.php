<?php

namespace App\Plugins\SteamaMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property float|null  $connection_fee
 * @property float       $subscription_cost
 * @property string      $payment_days_of_month
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SteamaHybridPaymentPlan extends BaseModel {
    protected $table = 'steama_hybrid_payment_plans';
}

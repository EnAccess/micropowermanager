<?php

namespace App\Plugins\SteamaMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property float       $plan_fee
 * @property string      $plan_duration
 * @property float|null  $energy_allotment
 * @property bool        $top_ups_enabled
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SteamaSubscriptionPaymentPlan extends BaseModel {
    protected $table = 'steama_subscription_payment_plans';
}

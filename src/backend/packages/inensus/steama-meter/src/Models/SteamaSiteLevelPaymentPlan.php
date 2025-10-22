<?php

namespace Inensus\SteamaMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property      int                                 $id
 * @property      int                                 $site_id
 * @property      int                                 $payment_plan_type_id
 * @property      int                                 $start
 * @property      int                                 $end
 * @property      float                               $value
 * @property      Carbon|null                         $created_at
 * @property      Carbon|null                         $updated_at
 * @property-read SteamaSiteLevelPaymentPlanType|null $planType
 * @property-read SteamaSite|null                     $site
 */
class SteamaSiteLevelPaymentPlan extends BaseModel {
    protected $table = 'steama_site_level_payment_plans';

    public function site() {
        return $this->belongsTo(SteamaSite::class, 'site_id');
    }

    public function planType() {
        return $this->belongsTo(SteamaSiteLevelPaymentPlanType::class, 'payment_plan_type_id');
    }
}

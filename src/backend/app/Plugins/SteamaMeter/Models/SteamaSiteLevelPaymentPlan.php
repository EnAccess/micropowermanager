<?php

namespace App\Plugins\SteamaMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    /**
     * @return BelongsTo<SteamaSite, $this>
     */
    public function site(): BelongsTo {
        return $this->belongsTo(SteamaSite::class, 'site_id');
    }

    /**
     * @return BelongsTo<SteamaSiteLevelPaymentPlanType, $this>
     */
    public function planType(): BelongsTo {
        return $this->belongsTo(SteamaSiteLevelPaymentPlanType::class, 'payment_plan_type_id');
    }
}

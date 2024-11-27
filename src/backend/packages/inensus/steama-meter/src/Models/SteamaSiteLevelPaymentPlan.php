<?php

namespace Inensus\SteamaMeter\Models;

class SteamaSiteLevelPaymentPlan extends BaseModel {
    protected $table = 'steama_site_level_payment_plans';

    public function site() {
        return $this->belongsTo(SteamaSite::class, 'site_id');
    }

    public function planType() {
        return $this->belongsTo(SteamaSiteLevelPaymentPlanType::class, 'payment_plan_type_id');
    }
}

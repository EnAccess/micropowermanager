<?php

namespace Inensus\SparkMeter\Models;

class SmSalesAccount extends BaseModel {
    protected $table = 'sm_sales_accounts';

    public function site() {
        return $this->belongsTo(SmSite::class, 'site_id', 'site_id');
    }
}

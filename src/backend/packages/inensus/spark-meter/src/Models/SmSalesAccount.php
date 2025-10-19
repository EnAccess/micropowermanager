<?php

namespace Inensus\SparkMeter\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property SmSite $site
 */
class SmSalesAccount extends \App\Models\Base\BaseModel {
    protected $table = 'sm_sales_accounts';

    public function site(): BelongsTo {
        return $this->belongsTo(SmSite::class, 'site_id', 'site_id');
    }
}

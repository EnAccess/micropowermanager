<?php

namespace Inensus\SparkMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property SmSite $site
 */
class SmSalesAccount extends BaseModel {
    protected $table = 'sm_sales_accounts';

    public function site(): BelongsTo {
        return $this->belongsTo(SmSite::class, 'site_id', 'site_id');
    }
}

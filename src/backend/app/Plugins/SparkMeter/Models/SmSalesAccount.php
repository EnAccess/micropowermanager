<?php

namespace App\Plugins\SparkMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property      int         $id
 * @property      string      $sales_account_id
 * @property      string      $site_id
 * @property      string      $name
 * @property      string      $account_type
 * @property      bool        $active
 * @property      float|null  $credit
 * @property      float|null  $markup
 * @property      string|null $hash
 * @property      Carbon|null $created_at
 * @property      Carbon|null $updated_at
 * @property-read SmSite|null $site
 */
class SmSalesAccount extends BaseModel {
    protected $table = 'sm_sales_accounts';

    /**
     * @return BelongsTo<SmSite, $this>
     */
    public function site(): BelongsTo {
        return $this->belongsTo(SmSite::class, 'site_id', 'site_id');
    }
}

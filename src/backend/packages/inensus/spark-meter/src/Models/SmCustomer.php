<?php

namespace Inensus\SparkMeter\Models;

use App\Models\Base\BaseModel;
use App\Models\Person\Person;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property float  $credit_balance
 * @property float  $low_balance_limit
 * @property Person $mpmPerson
 * @property SmSite $site
 */
class SmCustomer extends BaseModel {
    protected $table = 'sm_customers';

    public function mpmPerson(): BelongsTo {
        return $this->belongsTo(Person::class, 'mpm_customer_id');
    }

    public function site(): BelongsTo {
        return $this->belongsTo(SmSite::class, 'site_id', 'site_id');
    }
}

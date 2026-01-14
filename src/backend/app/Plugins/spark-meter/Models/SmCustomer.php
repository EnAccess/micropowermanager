<?php

namespace Inensus\SparkMeter\Models;

use App\Models\Base\BaseModel;
use App\Models\Person\Person;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property      int         $id
 * @property      string      $customer_id
 * @property      string      $site_id
 * @property      int         $mpm_customer_id
 * @property      float       $credit_balance
 * @property      float       $low_balance_limit
 * @property      string|null $hash
 * @property      Carbon|null $created_at
 * @property      Carbon|null $updated_at
 * @property-read Person|null $mpmPerson
 * @property-read SmSite|null $site
 */
class SmCustomer extends BaseModel {
    protected $table = 'sm_customers';

    /**
     * @return BelongsTo<Person, $this>
     */
    public function mpmPerson(): BelongsTo {
        return $this->belongsTo(Person::class, 'mpm_customer_id');
    }

    /**
     * @return BelongsTo<SmSite, $this>
     */
    public function site(): BelongsTo {
        return $this->belongsTo(SmSite::class, 'site_id', 'site_id');
    }
}

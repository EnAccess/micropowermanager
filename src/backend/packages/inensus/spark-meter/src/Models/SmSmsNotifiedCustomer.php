<?php

namespace Inensus\SparkMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property      int         $id
 * @property      string      $customer_id
 * @property      string      $notify_type
 * @property      string|null $notify_id
 * @property      Carbon|null $created_at
 * @property      Carbon|null $updated_at
 * @property-read Model|null  $notify
 */
class SmSmsNotifiedCustomer extends BaseModel {
    protected $table = 'sm_sms_notified_customers';

    /**
     * @return MorphTo<Model, $this>
     */
    public function notify(): MorphTo {
        return $this->morphTo();
    }
}

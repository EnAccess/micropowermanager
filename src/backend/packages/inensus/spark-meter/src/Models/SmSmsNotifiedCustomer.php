<?php

namespace Inensus\SparkMeter\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;

class SmSmsNotifiedCustomer extends BaseModel {
    protected $table = 'sm_sms_notified_customers';

    public function notify(): MorphTo {
        return $this->morphTo();
    }
}

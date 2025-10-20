<?php

namespace Inensus\SteamaMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SteamaSmsNotifiedCustomer extends BaseModel {
    protected $table = 'steama_sms_notified_customers';

    public function notify(): MorphTo {
        return $this->morphTo();
    }
}

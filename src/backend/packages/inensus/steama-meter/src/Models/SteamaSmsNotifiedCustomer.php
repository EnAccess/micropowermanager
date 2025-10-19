<?php

namespace Inensus\SteamaMeter\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;

class SteamaSmsNotifiedCustomer extends \App\Models\Base\BaseModel {
    protected $table = 'steama_sms_notified_customers';

    public function notify(): MorphTo {
        return $this->morphTo();
    }
}

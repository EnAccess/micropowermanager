<?php

namespace Inensus\SparkMeter\Models;

use App\Models\Base\BaseModel;

class SmSmsSetting extends BaseModel {
    protected $table = 'sm_sms_settings';

    public function setting() {
        return $this->morphOne(SmSetting::class, 'setting');
    }
}

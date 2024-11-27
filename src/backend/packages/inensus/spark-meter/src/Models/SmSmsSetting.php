<?php

namespace Inensus\SparkMeter\Models;

class SmSmsSetting extends BaseModel {
    protected $table = 'sm_sms_settings';

    public function setting() {
        return $this->morphOne(SmSetting::class, 'setting');
    }
}

<?php

namespace Inensus\SparkMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property      int            $id
 * @property      string         $state
 * @property      int            $not_send_elder_than_mins
 * @property      bool           $enabled
 * @property      Carbon|null    $created_at
 * @property      Carbon|null    $updated_at
 * @property-read SmSetting|null $setting
 */
class SmSmsSetting extends BaseModel {
    protected $table = 'sm_sms_settings';

    public function setting() {
        return $this->morphOne(SmSetting::class, 'setting');
    }
}

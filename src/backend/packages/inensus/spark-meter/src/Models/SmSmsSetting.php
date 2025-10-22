<?php

namespace Inensus\SparkMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\MorphOne;
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

    /**
     * @return MorphOne<SmSetting, $this>
     */
    public function setting(): MorphOne {
        return $this->morphOne(SmSetting::class, 'setting');
    }
}

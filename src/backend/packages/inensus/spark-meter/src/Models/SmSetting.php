<?php

namespace Inensus\SparkMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property      int             $id
 * @property      int             $setting_id
 * @property      string          $setting_type
 * @property      Carbon|null     $created_at
 * @property      Carbon|null     $updated_at
 * @property-read Model|\Eloquent $setting
 */
class SmSetting extends BaseModel {
    protected $table = 'sm_settings';

    public function setting(): MorphTo {
        return $this->morphTo();
    }
}

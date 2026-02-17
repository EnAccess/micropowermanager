<?php

namespace App\Plugins\KelinMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property      int              $id
 * @property      int              $setting_id
 * @property      string           $setting_type
 * @property      Carbon|null      $created_at
 * @property      Carbon|null      $updated_at
 * @property-read KelinSyncSetting $setting
 */
class KelinSetting extends BaseModel {
    protected $table = 'kelin_settings';

    /**
     * @return MorphTo<KelinSyncSetting, $this>
     */
    public function setting(): MorphTo {
        /** @var MorphTo<KelinSyncSetting, $this> */
        return $this->morphTo();
    }
}

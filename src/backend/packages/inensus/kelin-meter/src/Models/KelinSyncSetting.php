<?php

namespace Inensus\KelinMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;

/**
 * @property      int                  $id
 * @property      string               $action_name
 * @property      string               $sync_in_value_str
 * @property      int                  $sync_in_value_num
 * @property      int                  $max_attempts
 * @property      Carbon|null          $created_at
 * @property      Carbon|null          $updated_at
 * @property-read KelinSetting|null    $setting
 * @property-read KelinSyncAction|null $syncAction
 */
class KelinSyncSetting extends BaseModel {
    protected $table = 'kelin_sync_settings';

    /**
     * @return HasOne<KelinSyncAction, $this>
     */
    public function syncAction(): HasOne {
        return $this->hasOne(KelinSyncAction::class);
    }

    /**
     * @return MorphOne<KelinSetting, $this>
     */
    public function setting(): MorphOne {
        return $this->morphOne(KelinSetting::class, 'setting');
    }
}

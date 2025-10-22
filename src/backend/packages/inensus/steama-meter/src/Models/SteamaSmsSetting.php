<?php

namespace Inensus\SteamaMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property      int                $id
 * @property      string             $state
 * @property      int                $not_send_elder_than_mins
 * @property      bool               $enabled
 * @property      Carbon|null        $created_at
 * @property      Carbon|null        $updated_at
 * @property-read SteamaSetting|null $setting
 */
class SteamaSmsSetting extends BaseModel {
    protected $table = 'steama_sms_settings';

    public function setting() {
        return $this->morphOne(SteamaSetting::class, 'setting');
    }
}

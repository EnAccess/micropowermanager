<?php

namespace App\Plugins\SteamaMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\MorphOne;
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

    /**
     * @return MorphOne<SteamaSetting, $this>
     */
    public function setting(): MorphOne {
        return $this->morphOne(SteamaSetting::class, 'setting');
    }
}

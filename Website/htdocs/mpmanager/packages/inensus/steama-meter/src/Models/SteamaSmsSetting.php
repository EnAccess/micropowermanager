<?php

namespace Inensus\SteamaMeter\Models;

class SteamaSmsSetting extends BaseModel
{
    protected $table = 'steama_sms_settings';

    public function setting()
    {
        return $this->morphOne(SteamaSetting::class, 'setting');
    }
}

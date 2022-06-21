<?php

namespace Inensus\SteamaMeter\Services;

use Inensus\SteamaMeter\Models\SteamaSetting;
use Inensus\SteamaMeter\Models\SteamaSmsSetting;
use Inensus\SteamaMeter\Models\SteamaSyncSetting;

class SteamaSettingService
{

    private $steamaSetting;

    public function __construct(SteamaSetting $steamaSetting)
    {
        $this->steamaSetting = $steamaSetting;
    }

    public function getSettings()
    {

        return   $this->steamaSetting->newQuery()->with(['settingSms','settingSync'])->whereHasMorph('setting', '*')->get();
    }
}

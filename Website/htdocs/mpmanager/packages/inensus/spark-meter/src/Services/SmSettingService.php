<?php

namespace Inensus\SparkMeter\Services;

use Inensus\SparkMeter\Models\SmSetting;
use Inensus\SparkMeter\Models\SmSmsSetting;
use Inensus\SparkMeter\Models\SmSyncSetting;

class SmSettingService
{
    private $smSetting;

    public function __construct(SmSetting $smSetting)
    {
        $this->smSetting = $smSetting;
    }

    public function getSettings()
    {

        return $this->smSetting->newQuery()->with(['settingSms','settingSync'])->whereHasMorph('setting', '*')->get();
    }
}

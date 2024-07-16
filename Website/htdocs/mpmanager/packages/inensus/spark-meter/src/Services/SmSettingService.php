<?php

namespace Inensus\SparkMeter\Services;

use Inensus\SparkMeter\Models\SmSetting;

class SmSettingService
{
    private $smSetting;

    public function __construct(SmSetting $smSetting)
    {
        $this->smSetting = $smSetting;
    }

    public function getSettings()
    {
        return $this->smSetting->newQuery()->with(['settingSms', 'settingSync'])->whereHasMorph('setting', '*')->get();
    }
}

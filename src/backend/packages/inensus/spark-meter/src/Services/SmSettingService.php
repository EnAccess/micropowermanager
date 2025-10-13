<?php

namespace Inensus\SparkMeter\Services;

use Inensus\SparkMeter\Models\SmSetting;

class SmSettingService {
    public function __construct(private SmSetting $smSetting) {}

    public function getSettings() {
        return $this->smSetting->newQuery()->whereHasMorph('setting', '*')->get();
    }
}

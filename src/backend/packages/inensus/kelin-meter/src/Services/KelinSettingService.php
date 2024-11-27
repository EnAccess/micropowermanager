<?php

namespace Inensus\KelinMeter\Services;

use Inensus\KelinMeter\Models\KelinSetting;

class KelinSettingService {
    private $kelinSetting;

    public function __construct(KelinSetting $kelinSetting) {
        $this->kelinSetting = $kelinSetting;
    }

    public function getSettings() {
        return $this->kelinSetting->newQuery()->with(['setting'])->get();
    }
}

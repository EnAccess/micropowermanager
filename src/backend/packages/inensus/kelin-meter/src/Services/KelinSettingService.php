<?php

namespace Inensus\KelinMeter\Services;

use Inensus\KelinMeter\Models\KelinSetting;

class KelinSettingService {
    public function __construct(private KelinSetting $kelinSetting) {}

    public function getSettings() {
        return $this->kelinSetting->newQuery()->with(['setting'])->get();
    }
}

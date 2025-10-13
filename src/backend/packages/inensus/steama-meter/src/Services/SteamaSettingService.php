<?php

namespace Inensus\SteamaMeter\Services;

use Inensus\SteamaMeter\Models\SteamaSetting;

class SteamaSettingService {
    public function __construct(private SteamaSetting $steamaSetting) {}

    public function getSettings() {
        return $this->steamaSetting->newQuery()->whereHasMorph('setting', '*')->get();
    }
}

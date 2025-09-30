<?php

namespace Inensus\SteamaMeter\Services;

use Inensus\SteamaMeter\Models\SteamaSetting;

class SteamaSettingService {
    private SteamaSetting $steamaSetting;

    public function __construct(SteamaSetting $steamaSetting) {
        $this->steamaSetting = $steamaSetting;
    }

    public function getSettings() {
        return $this->steamaSetting->newQuery()->whereHasMorph('setting', '*')->get();
    }
}

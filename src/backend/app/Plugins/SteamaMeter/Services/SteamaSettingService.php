<?php

namespace App\Plugins\SteamaMeter\Services;

use App\Plugins\SteamaMeter\Models\SteamaSetting;
use Illuminate\Database\Eloquent\Collection;

class SteamaSettingService {
    public function __construct(
        private SteamaSetting $steamaSetting,
    ) {}

    /**
     * @return Collection<int, SteamaSetting>
     */
    public function getSettings(): Collection {
        return $this->steamaSetting->newQuery()->whereHasMorph('setting', '*')->get();
    }
}

<?php

namespace Inensus\SteamaMeter\Services;

use Illuminate\Database\Eloquent\Collection;
use Inensus\SteamaMeter\Models\SteamaSetting;

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

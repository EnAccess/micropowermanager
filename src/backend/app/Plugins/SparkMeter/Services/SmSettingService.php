<?php

namespace App\Plugins\SparkMeter\Services;

use App\Plugins\SparkMeter\Models\SmSetting;
use Illuminate\Database\Eloquent\Collection;

class SmSettingService {
    public function __construct(
        private SmSetting $smSetting,
    ) {}

    /**
     * @return Collection<int, SmSetting>
     */
    public function getSettings(): Collection {
        return $this->smSetting->newQuery()->whereHasMorph('setting', '*')->get();
    }
}

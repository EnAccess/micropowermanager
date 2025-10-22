<?php

namespace Inensus\SparkMeter\Services;

use Illuminate\Database\Eloquent\Collection;
use Inensus\SparkMeter\Models\SmSetting;

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

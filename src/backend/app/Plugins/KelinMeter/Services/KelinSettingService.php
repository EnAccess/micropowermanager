<?php

namespace App\Plugins\KelinMeter\Services;

use App\Plugins\KelinMeter\Models\KelinSetting;
use Illuminate\Database\Eloquent\Collection;

class KelinSettingService {
    public function __construct(
        private KelinSetting $kelinSetting,
    ) {}

    /**
     * @return Collection<int, KelinSetting>
     */
    public function getSettings(): Collection {
        return $this->kelinSetting->newQuery()->with(['setting'])->get();
    }
}

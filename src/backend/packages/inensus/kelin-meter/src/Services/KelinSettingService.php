<?php

namespace Inensus\KelinMeter\Services;

use Illuminate\Database\Eloquent\Collection;
use Inensus\KelinMeter\Models\KelinSetting;

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

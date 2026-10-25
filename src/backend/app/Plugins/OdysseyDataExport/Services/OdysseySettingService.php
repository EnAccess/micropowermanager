<?php

namespace App\Plugins\OdysseyDataExport\Services;

use App\Plugins\OdysseyDataExport\Models\OdysseyFinancingProgram;
use App\Plugins\OdysseyDataExport\Models\OdysseySetting;

class OdysseySettingService {
    public function __construct(
        private OdysseySetting $setting,
    ) {}

    public function getSettings(): OdysseySetting {
        return $this->setting->newQuery()->firstOrNew();
    }

    public function updateFinancingProgram(?OdysseyFinancingProgram $financingProgram): OdysseySetting {
        $setting = $this->getSettings();
        $setting->financing_id = $financingProgram;
        $setting->save();

        return $setting;
    }
}

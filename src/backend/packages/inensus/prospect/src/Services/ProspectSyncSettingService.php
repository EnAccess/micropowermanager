<?php

namespace Inensus\Prospect\Services;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Inensus\Prospect\Models\ProspectSyncSetting;

class ProspectSyncSettingService {
    public function __construct(private ProspectSyncSetting $syncSetting) {}

    public function updateSyncSettings(array $syncSettings) {
        foreach ($syncSettings as $setting) {
            $this->syncSetting->newQuery()->updateOrCreate(
                ['id' => $setting['id']],
                [
                    'action_name' => $setting['action_name'],
                    'sync_in_value_str' => $setting['sync_in_value_str'],
                    'sync_in_value_num' => $setting['sync_in_value_num'],
                    'max_attempts' => $setting['max_attempts'],
                ]
            );
        }

        return $this->syncSetting->newQuery()->get();
    }
}



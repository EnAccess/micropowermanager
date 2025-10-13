<?php

namespace Inensus\Prospect\Services;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Inensus\Prospect\Models\ProspectSyncSetting;

class ProspectSyncSettingService {
    public function __construct(private ProspectSyncSetting $syncSetting) {}

    public function updateSyncSettings(array $syncSettings) {
        foreach ($syncSettings as $setting) {
            $record = $this->syncSetting->newQuery()->find($setting['id']);
            if ($record) {
                $record->update([
                    'max_attempts' => $setting['max_attempts'] ?? $record->max_attempts,
                    'sync_in_value_str' => $setting['sync_in_value_str'] ?? $record->sync_in_value_str,
                    'sync_in_value_num' => $setting['sync_in_value_num'] ?? $record->sync_in_value_num,
                ]);
            }
        }

        return $this->syncSetting->newQuery()->get();
    }
}



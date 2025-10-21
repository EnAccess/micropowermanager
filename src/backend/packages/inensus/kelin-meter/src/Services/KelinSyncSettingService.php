<?php

namespace Inensus\KelinMeter\Services;

use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Date;
use Inensus\KelinMeter\Models\KelinSetting;
use Inensus\KelinMeter\Models\KelinSyncSetting;

class KelinSyncSettingService {
    public function __construct(
        private KelinSyncSetting $syncSetting,
        private KelinSetting $setting,
        private KelinSyncActionService $syncActionService,
    ) {}

    public function createDefaultSettings(): void {
        $dayInterval = CarbonInterval::make('1day');
        CarbonInterval::make('5minute');

        $syncCustomer = $this->syncSetting->newQuery()->where('action_name', 'Customers')->first();
        if (!$syncCustomer) {
            $now = Date::now();
            $customerSetting = $this->setting->newQuery()->make();
            $syncCustomer = $this->syncSetting->newQuery()->create([
                'action_name' => 'Customers',
                'sync_in_value_str' => 'day',
                'sync_in_value_num' => 1,
            ]);
            $customerSetting->setting()->associate($syncCustomer);
            $customerSetting->save();
            $syncAction = [
                'sync_setting_id' => $syncCustomer->id,
                'next_sync' => $now->add($dayInterval),
            ];
            $this->syncActionService->createSyncAction($syncAction);
        }

        $syncMeter = $this->syncSetting->newQuery()->where('action_name', 'Meters')->first();
        if (!$syncMeter) {
            $now = Date::now();
            $meterSetting = $this->setting->newQuery()->make();
            $syncMeter = $this->syncSetting->newQuery()->create([
                'action_name' => 'Meters',
                'sync_in_value_str' => 'day',
                'sync_in_value_num' => 1,
            ]);
            $meterSetting->setting()->associate($syncMeter);
            $meterSetting->save();
            $syncAction = [
                'sync_setting_id' => $syncMeter->id,
                'next_sync' => $now->add($dayInterval),
            ];
            $this->syncActionService->createSyncAction($syncAction);
        }
    }

    /**
     * @param array<string, mixed> $syncSettings
     *
     * @return Collection<int, KelinSyncSetting>
     */
    public function updateSyncSettings(array $syncSettings): Collection {
        foreach ($syncSettings as $setting) {
            $syncSetting = $this->syncSetting->newQuery()->find($setting['id']);
            $intervalStr = $setting['sync_in_value_num'].$setting['sync_in_value_str'];
            $syncSettingAction = $this->syncActionService->getSyncActionBySynSettingId($setting['id']);

            if ($syncSetting) {
                $date = Date::now();
                $interval = CarbonInterval::make($intervalStr);

                $syncSetting->update([
                    'max_attempts' => $setting['max_attempts'],
                    'sync_in_value_str' => $setting['sync_in_value_str'],
                    'sync_in_value_num' => $setting['sync_in_value_num'],
                ]);

                $syncSettingAction->update([
                    'next_sync' => $date->add($interval),
                ]);
            }
        }

        return $this->syncSetting->newQuery()->get();
    }

    /**
     * @return Collection<int, KelinSyncSetting>
     */
    public function getSyncSettings() {
        return $this->syncSetting->newQuery()->get();
    }

    public function getSyncSettingsByActionName(string $actionName): KelinSyncSetting {
        try {
            return $this->syncSetting->newQuery()->where('action_name', $actionName)->firstOrFail();
        } catch (\Exception $exception) {
            throw new ModelNotFoundException($exception->getMessage());
        }
    }
}

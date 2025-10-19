<?php

namespace Inensus\SteamaMeter\Services;

use Carbon\CarbonInterval;
use Illuminate\Support\Facades\Date;
use Inensus\SteamaMeter\Exceptions\ModelNotFoundException;
use Inensus\SteamaMeter\Models\SteamaSetting;
use Inensus\SteamaMeter\Models\SteamaSyncSetting;

class SteamaSyncSettingService {
    public function __construct(private SteamaSyncSetting $syncSetting, private SteamaSetting $setting, private StemaSyncActionService $syncActionService) {}

    public function createDefaultSettings(): void {
        $dayInterval = CarbonInterval::make('1day');
        $fiveMinInterval = CarbonInterval::make('5minute');

        $syncSite = $this->syncSetting->newQuery()->where('action_name', 'Sites')->first();

        if (!$syncSite) {
            $now = Date::now();
            $siteSetting = $this->setting->newQuery()->make();
            $syncSite = $this->syncSetting->newQuery()->create([
                'action_name' => 'Sites',
                'sync_in_value_str' => 'day',
                'sync_in_value_num' => 1,
            ]);
            $siteSetting->setting()->associate($syncSite);
            $siteSetting->save();
            $syncAction = [
                'sync_setting_id' => $syncSite->id,
                'next_sync' => $now->add($dayInterval),
            ];
            $this->syncActionService->createSyncAction($syncAction);
        }

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

        $syncAgent = $this->syncSetting->newQuery()->where('action_name', 'Agents')->first();
        if (!$syncAgent) {
            $now = Date::now();
            $agentSetting = $this->setting->newQuery()->make();
            $syncAgent = $this->syncSetting->newQuery()->create([
                'action_name' => 'Agents',
                'sync_in_value_str' => 'day',
                'sync_in_value_num' => 1,
            ]);
            $agentSetting->setting()->associate($syncAgent);
            $agentSetting->save();
            $syncAction = [
                'sync_setting_id' => $syncAgent->id,
                'next_sync' => $now->add($dayInterval),
            ];
            $this->syncActionService->createSyncAction($syncAction);
        }

        $syncTransaction = $this->syncSetting->newQuery()->where('action_name', 'Transactions')->first();
        if (!$syncTransaction) {
            $now = Date::now();
            $transactionSetting = $this->setting->newQuery()->make();
            $syncTransaction = $this->syncSetting->newQuery()->create([
                'action_name' => 'Transactions',
                'sync_in_value_str' => 'minute',
                'sync_in_value_num' => 5,
            ]);
            $transactionSetting->setting()->associate($syncTransaction);
            $transactionSetting->save();
            $syncAction = [
                'sync_setting_id' => $syncTransaction->id,
                'next_sync' => $now->add($fiveMinInterval),
            ];
            $this->syncActionService->createSyncAction($syncAction);
        }
    }

    public function updateSyncSettings($syncSettings) {
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

    public function getSyncSettings() {
        return $this->syncSetting->newQuery()->get();
    }

    public function getSyncSettingsByActionName($actionName) {
        try {
            return $this->syncSetting->newQuery()->where('action_name', $actionName)->firstOrFail();
        } catch (\Exception $exception) {
            throw new ModelNotFoundException($exception->getMessage());
        }
    }
}

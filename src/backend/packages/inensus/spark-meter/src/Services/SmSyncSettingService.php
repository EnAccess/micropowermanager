<?php

namespace Inensus\SparkMeter\Services;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Inensus\SparkMeter\Models\SmSetting;
use Inensus\SparkMeter\Models\SmSyncSetting;

class SmSyncSettingService {
    private $syncSetting;
    private $setting;
    private $syncActionService;

    public function __construct(SmSyncSetting $syncSetting, SmSetting $setting, SmSyncActionService $syncActionService) {
        $this->syncSetting = $syncSetting;
        $this->setting = $setting;
        $this->syncActionService = $syncActionService;
    }

    public function createDefaultSettings() {
        $dayInterval = CarbonInterval::make('1day');
        $fiveMinInterval = CarbonInterval::make('5minute');

        $syncSite = $this->syncSetting->newQuery()->where('action_name', 'Sites')->first();

        if (!$syncSite) {
            $now = Carbon::now();
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

        $syncMeterMotel = $this->syncSetting->newQuery()->where('action_name', 'MeterModels')->first();
        if (!$syncMeterMotel) {
            $now = Carbon::now();
            $meterModelSetting = $this->setting->newQuery()->make();
            $syncMeterMotel = $this->syncSetting->newQuery()->create([
                'action_name' => 'MeterModels',
                'sync_in_value_str' => 'day',
                'sync_in_value_num' => 1,
            ]);
            $meterModelSetting->setting()->associate($syncMeterMotel);
            $meterModelSetting->save();
            $syncAction = [
                'sync_setting_id' => $syncMeterMotel->id,
                'next_sync' => $now->add($dayInterval),
            ];
            $this->syncActionService->createSyncAction($syncAction);
        }

        $syncTariff = $this->syncSetting->newQuery()->where('action_name', 'Tariffs')->first();
        if (!$syncTariff) {
            $now = Carbon::now();
            $tariffSetting = $this->setting->newQuery()->make();
            $syncTariff = $this->syncSetting->newQuery()->create([
                'action_name' => 'Tariffs',
                'sync_in_value_str' => 'day',
                'sync_in_value_num' => 1,
            ]);
            $tariffSetting->setting()->associate($syncTariff);
            $tariffSetting->save();
            $syncAction = [
                'sync_setting_id' => $syncTariff->id,
                'next_sync' => $now->add($dayInterval),
            ];
            $this->syncActionService->createSyncAction($syncAction);
        }

        $syncCustomer = $this->syncSetting->newQuery()->where('action_name', 'Customers')->first();
        if (!$syncCustomer) {
            $now = Carbon::now();
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

        $syncTransaction = $this->syncSetting->newQuery()->where('action_name', 'Transactions')->first();
        if (!$syncTransaction) {
            $now = Carbon::now();
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

        $syncSalesAccount = $this->syncSetting->newQuery()->where('action_name', 'SalesAccounts')->first();
        if (!$syncSalesAccount) {
            $now = Carbon::now();
            $salesAccountSetting = $this->setting->newQuery()->make();
            $syncSalesAccount = $this->syncSetting->newQuery()->create([
                'action_name' => 'SalesAccounts',
                'sync_in_value_str' => 'minute',
                'sync_in_value_num' => 5,
            ]);
            $salesAccountSetting->setting()->associate($syncSalesAccount);
            $salesAccountSetting->save();
            $syncAction = [
                'sync_setting_id' => $syncSalesAccount->id,
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
                $date = Carbon::now();
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
        } catch (ModelNotFoundException $exception) {
            throw new ModelNotFoundException($exception->getMessage());
        }
    }
}

<?php

namespace Inensus\SparkMeter\Services;

use Illuminate\Support\Facades\Log;
use Inensus\SparkMeter\Exceptions\SparkAPIResponseException;
use Inensus\SparkMeter\Helpers\SmTableEncryption;
use Inensus\SparkMeter\Http\Requests\SparkMeterApiRequests;
use Inensus\SparkMeter\Models\SmSalesAccount;
use Inensus\SparkMeter\Models\SmSite;
use Inensus\SparkMeter\Models\SyncStatus;

class SmSalesAccoutService implements ISynchronizeService {
    private $sparkMeterApiRequests;
    private $rootUrl = '/sales-accounts';
    private $smTableEncryption;
    private $smSalesAccount;
    private $smSite;
    private $smSyncSettingService;
    private $smSyncActionService;

    public function __construct(
        SparkMeterApiRequests $sparkMeterApiRequests,
        SmTableEncryption $smTableEncryption,
        SmSalesAccount $smSalesAccount,
        SmSite $smSite,
        SmSyncSettingService $smSyncSettingService,
        SmSyncActionService $smSyncActionService,
    ) {
        $this->sparkMeterApiRequests = $sparkMeterApiRequests;
        $this->smTableEncryption = $smTableEncryption;
        $this->smSalesAccount = $smSalesAccount;
        $this->smSite = $smSite;
        $this->smSyncSettingService = $smSyncSettingService;
        $this->smSyncActionService = $smSyncActionService;
    }

    public function getSmSalesAccounts($request) {
        $perPage = $request->input('per_page') ?? 15;

        return $this->smSalesAccount->newQuery()->with(['site.mpmMiniGrid'])->paginate($perPage);
    }

    public function getSmSalesAccountsCount() {
        return count($this->smSalesAccount->newQuery()->get());
    }

    public function sync() {
        $synSetting = $this->smSyncSettingService->getSyncSettingsByActionName('SalesAccounts');
        $syncAction = $this->smSyncActionService->getSyncActionBySynSettingId($synSetting->id);
        try {
            $syncCheck = $this->syncCheck(true);
            $salesAccountsCollection = collect($syncCheck)->except('available_site_count');
            $salesAccountsCollection->each(function ($salesAccounts) {
                $salesAccounts['site_data']->filter(function ($salesAccount) {
                    return $salesAccount['syncStatus'] === SyncStatus::NOT_REGISTERED_YET;
                })->each(function ($salesAccount) use ($salesAccounts) {
                    $this->smSalesAccount->newQuery()->create([
                        'sales_account_id' => $salesAccount['id'],
                        'account_type' => $salesAccount['account_type'],
                        'active' => $salesAccount['active'],
                        'credit' => $salesAccount['credit'],
                        'name' => $salesAccount['name'],
                        'markup' => $salesAccount['markup'],
                        'site_id' => $salesAccounts['site_id'],
                        'hash' => $salesAccount['hash'],
                    ]);
                });

                $salesAccounts['site_data']->filter(function ($salesAccount) {
                    return $salesAccount['syncStatus'] === SyncStatus::MODIFIED;
                })->each(function ($salesAccount) use ($salesAccounts) {
                    $salesAccount['registeredSparkSalesAccount']->update([
                        'account_type' => $salesAccount['account_type'],
                        'active' => $salesAccount['active'],
                        'credit' => $salesAccount['credit'],
                        'name' => $salesAccount['name'],
                        'markup' => $salesAccount['markup'],
                        'site_id' => $salesAccounts['site_id'],
                        'hash' => $salesAccount['hash'],
                    ]);
                });
            });
            $this->smSyncActionService->updateSyncAction($syncAction, $synSetting, true);

            return $this->smSalesAccount->newQuery()->with([
                'site.mpmMiniGrid',
            ])->paginate(config('paginate.paginate'));
        } catch (\Exception $e) {
            $this->smSyncActionService->updateSyncAction($syncAction, $synSetting, false);
            Log::critical('Spark sales account sync failed.', ['Error :' => $e->getMessage()]);
            throw new \Exception($e->getMessage());
        }
    }

    public function syncCheck($returnData = false) {
        $returnArray = ['available_site_count' => 0];
        $sites = $this->smSite->newQuery()->where('is_authenticated', 1)->where('is_online', 1)->get();

        foreach ($sites as $key => $site) {
            $returnArray['available_site_count'] = $key + 1;
            $url = $this->rootUrl;
            try {
                $sparkSalesAccounts = $this->sparkMeterApiRequests->get($url, $site->site_id);
            } catch (\Exception $e) {
                Log::critical('Spark meter sales-accounts sync-check failed.', ['Error :' => $e->getMessage()]);
                if ($returnData) {
                    array_push(
                        $returnArray,
                        ['result' => false]
                    );
                }
                throw new SparkAPIResponseException($e->getMessage());
            }
            $sparkSalesAccountsCollection = collect($sparkSalesAccounts['accounts']);
            $salesAccounts = $this->smSalesAccount->newQuery()->where('site_id', $site->site_id)->get();
            $sparkSalesAccountsCollection->transform(function ($salesAccount) use ($salesAccounts) {
                $registeredSparkSalesAccount = $salesAccounts->firstWhere('sales_account_id', $salesAccount['id']);
                $salesAccountsHash = $this->modelHasher($salesAccount, null);
                if ($registeredSparkSalesAccount) {
                    $salesAccount['syncStatus'] = $salesAccountsHash === $registeredSparkSalesAccount->hash ?
                        SyncStatus::SYNCED : SyncStatus::MODIFIED;
                } else {
                    $salesAccount['syncStatus'] = SyncStatus::NOT_REGISTERED_YET;
                }
                $salesAccount['hash'] = $salesAccountsHash;
                $salesAccount['registeredSparkSalesAccount'] = $registeredSparkSalesAccount;

                return $salesAccount;
            });

            $salesAccountsSyncStatus = $sparkSalesAccountsCollection->whereNotIn('syncStatus', SyncStatus::SYNCED)->count();
            if ($salesAccountsSyncStatus) {
                $returnData ? array_push($returnArray, [
                    'site_id' => $site->site_id,
                    'site_data' => $sparkSalesAccountsCollection,
                    'result' => false,
                ]) : array_push($returnArray, ['result' => false]);
            } else {
                $returnData ? array_push($returnArray, [
                    'site_id' => $site->site_id,
                    'site_data' => $sparkSalesAccountsCollection,
                    'result' => true,
                ]) : array_push($returnArray, ['result' => true]);
            }
        }

        return $returnArray;
    }

    public function syncCheckBySite($siteId) {
        try {
            $url = $this->rootUrl;
            $sparkMeterModels = $this->sparkMeterApiRequests->get($url, $siteId);
        } catch (\Exception $e) {
            Log::critical('Spark meter sales-account sync-check-by-site failed.', ['Error :' => $e->getMessage()]);
            throw new SparkAPIResponseException($e->getMessage());
        }
        $sparkSalesAccountsCollection = collect($sparkMeterModels['accounts']);

        $salesAccounts = $this->smSalesAccount->newQuery()->where('site_id', $siteId)->get();

        $sparkSalesAccountsCollection->transform(function ($salesAccount) use ($salesAccounts) {
            $registeredSparkSalesAccount = $salesAccounts->firstWhere('id', $salesAccount['id']);
            $salesAccountHash = $this->modelHasher($salesAccount, null);
            if ($registeredSparkSalesAccount) {
                $salesAccount['syncStatus'] = $salesAccountHash === $registeredSparkSalesAccount->hash ?
                    SyncStatus::SYNCED : SyncStatus::MODIFIED;
            } else {
                $salesAccount['syncStatus'] = SyncStatus::NOT_REGISTERED_YET;
            }
            $salesAccount['hash'] = $salesAccountHash;
            $salesAccount['registeredSparkSalesAccount'] = $registeredSparkSalesAccount;

            return $salesAccount;
        });

        $salesAccountSyncStatus = $sparkSalesAccountsCollection->whereNotIn('syncStatus', 1)->count();

        if ($salesAccountSyncStatus) {
            return ['result' => false, 'message' => 'sales accounts are not updated for site '.$siteId];
        } else {
            return ['result' => true, 'message' => 'Records are updated'];
        }
    }

    public function modelHasher($model, ...$params): string {
        return $smModelHash = $this->smTableEncryption->makeHash([
            $model['account_type'],
            $model['active'],
            $model['credit'],
            $model['name'],
            $model['markup'],
        ]);
    }
}

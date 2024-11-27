<?php

namespace Inensus\SparkMeter\Services;

use App\Models\Meter\MeterType;
use Illuminate\Support\Facades\Log;
use Inensus\SparkMeter\Exceptions\SparkAPIResponseException;
use Inensus\SparkMeter\Helpers\SmTableEncryption;
use Inensus\SparkMeter\Http\Requests\SparkMeterApiRequests;
use Inensus\SparkMeter\Models\SmMeterModel;
use Inensus\SparkMeter\Models\SmSite;
use Inensus\SparkMeter\Models\SyncStatus;

class MeterModelService implements ISynchronizeService {
    private $sparkMeterApiRequests;
    private $rootUrl = '/meters';
    private $smTableEncryption;
    private $smMeterModel;
    private $smSite;
    private $meterType;
    private $smSyncSettingService;
    private $smSyncActionService;

    public function __construct(
        SparkMeterApiRequests $sparkMeterApiRequests,
        SmTableEncryption $smTableEncryption,
        SmMeterModel $smMeterModel,
        SmSite $smSite,
        MeterType $meterType,
        SmSyncSettingService $smSyncSettingService,
        SmSyncActionService $smSyncActionService,
    ) {
        $this->sparkMeterApiRequests = $sparkMeterApiRequests;
        $this->smTableEncryption = $smTableEncryption;
        $this->smMeterModel = $smMeterModel;
        $this->smSite = $smSite;
        $this->meterType = $meterType;
        $this->smSyncSettingService = $smSyncSettingService;
        $this->smSyncActionService = $smSyncActionService;
    }

    public function getSmMeterModels($request) {
        $perPage = $request->input('per_page') ?? 15;

        return $this->smMeterModel->newQuery()->with(['meterType', 'site.mpmMiniGrid'])->paginate($perPage);
    }

    public function getSmMeterModelsCount() {
        return count($this->smMeterModel->newQuery()->get());
    }

    public function sync() {
        $synSetting = $this->smSyncSettingService->getSyncSettingsByActionName('MeterModels');
        $syncAction = $this->smSyncActionService->getSyncActionBySynSettingId($synSetting->id);
        try {
            $syncCheck = $this->syncCheck(true);
            $meterModelsCollection = collect($syncCheck)->except('available_site_count');
            $meterModelsCollection->each(function ($meterModels) {
                $meterModels['site_data']->filter(function ($meterModel) {
                    return $meterModel['syncStatus'] === SyncStatus::NOT_REGISTERED_YET;
                })->each(function ($meterModel) use ($meterModels) {
                    $meterType = $this->meterType->newQuery()->create([
                        'online' => 1,
                        'phase' => $meterModel['phase_count'],
                        'max_current' => $meterModel['continuous_limit'],
                    ]);
                    $this->smMeterModel->newQuery()->create([
                        'model_name' => $meterModel['name'],
                        'mpm_meter_type_id' => $meterType->id,
                        'continuous_limit' => $meterModel['continuous_limit'],
                        'inrush_limit' => $meterModel['inrush_limit'],
                        'site_id' => $meterModels['site_id'],
                        'hash' => $meterModel['hash'],
                    ]);
                });

                $meterModels['site_data']->filter(function ($meterModel) {
                    return $meterModel['syncStatus'] === SyncStatus::MODIFIED;
                })->each(function ($meterModel) use ($meterModels) {
                    is_null($meterModel['relatedMeterType']) ?
                        $this->createRelatedMeterModel($meterModel) : $this->updateRelatedMeterModel(
                            $meterModel,
                            $meterModel['relatedMeterType']
                        );
                    $meterModel['registeredSparkMeterModel']->update([
                        'model_name' => $meterModel['name'],
                        'continuous_limit' => $meterModel['continuous_limit'],
                        'inrush_limit' => $meterModel['inrush_limit'],
                        'site_id' => $meterModels['site_id'],
                        'hash' => $meterModel['hash'],
                    ]);
                });
            });
            $this->smSyncActionService->updateSyncAction($syncAction, $synSetting, true);

            return $this->smMeterModel->newQuery()->with([
                'meterType',
                'site.mpmMiniGrid',
            ])->paginate(config('paginate.paginate'));
        } catch (\Exception $e) {
            $this->smSyncActionService->updateSyncAction($syncAction, $synSetting, false);
            Log::critical('Spark meter models sync failed.', ['Error :' => $e->getMessage()]);
            throw new \Exception($e->getMessage());
        }
    }

    public function syncCheck($returnData = false) {
        $returnArray = ['available_site_count' => 0];
        $sites = $this->smSite->newQuery()->where('is_authenticated', 1)->where('is_online', 1)->get();
        foreach ($sites as $key => $site) {
            $returnArray['available_site_count'] = $key + 1;
            $url = $this->rootUrl.'/models';
            try {
                $sparkMeterModels = $this->sparkMeterApiRequests->get($url, $site->site_id);
            } catch (SparkAPIResponseException $e) {
                Log::critical('Spark meter meter-models sync-check failed.', ['Error :' => $e->getMessage()]);
                if ($returnData) {
                    array_push(
                        $returnArray,
                        ['result' => false]
                    );
                }
                throw new SparkAPIResponseException($e->getMessage());
            }
            $sparkMeterModelsCollection = collect($sparkMeterModels['models']);

            $meterModels = $this->smMeterModel->newQuery()->where('site_id', $site->site_id)->get();
            $meterTypes = $this->meterType->newQuery()->get();

            $sparkMeterModelsCollection->transform(function ($meterModel) use ($meterModels, $meterTypes) {
                $registeredSparkMeterModel = $meterModels->firstWhere('model_name', $meterModel['name']);
                $relatedMeterType = null;
                $meterModelHash = $this->modelHasher($meterModel, null);
                if ($registeredSparkMeterModel) {
                    $meterModel['syncStatus'] = $meterModelHash === $registeredSparkMeterModel->hash ?
                        SyncStatus::SYNCED : SyncStatus::MODIFIED;
                    $relatedMeterType = $meterTypes->find($registeredSparkMeterModel->mpm_meter_type_id);
                } else {
                    $meterModel['syncStatus'] = SyncStatus::NOT_REGISTERED_YET;
                }
                $meterModel['hash'] = $meterModelHash;
                $meterModel['relatedMeterType'] = $relatedMeterType;
                $meterModel['registeredSparkMeterModel'] = $registeredSparkMeterModel;

                return $meterModel;
            });

            $meterModelSyncStatus = $sparkMeterModelsCollection->whereNotIn('syncStatus', 1)->count();

            if ($meterModelSyncStatus) {
                $returnData ? array_push($returnArray, [
                    'site_id' => $site->site_id,
                    'site_data' => $sparkMeterModelsCollection,
                    'result' => false,
                ]) : array_push($returnArray, ['result' => false]);
            } else {
                $returnData ? array_push($returnArray, [
                    'site_id' => $site->site_id,
                    'site_data' => $sparkMeterModelsCollection,
                    'result' => true,
                ]) : array_push($returnArray, ['result' => true]);
            }
        }

        return $returnArray;
    }

    public function modelHasher($model, ...$params): string {
        return $smModelHash = $this->smTableEncryption->makeHash([
            $model['name'],
            $model['phase_count'],
            $model['continuous_limit'],
            $model['inrush_limit'],
        ]);
    }

    public function syncCheckBySite($siteId) {
        try {
            $url = $this->rootUrl.'/models';
            $sparkMeterModels = $this->sparkMeterApiRequests->get($url, $siteId);
        } catch (\Exception $e) {
            Log::critical('Spark meter meter-models sync-check-by-site failed.', ['Error :' => $e->getMessage()]);
            throw new SparkAPIResponseException($e->getMessage());
        }
        $sparkMeterModelsCollection = collect($sparkMeterModels['models']);

        $meterModels = $this->smMeterModel->newQuery()->where('site_id', $siteId)->get();
        $meterTypes = $this->meterType->newQuery()->get();

        $sparkMeterModelsCollection->transform(function ($meterModel) use ($meterModels, $meterTypes) {
            $registeredSparkMeterModel = $meterModels->firstWhere('model_name', $meterModel['name']);
            $relatedMeterType = null;
            $meterModelHash = $this->modelHasher($meterModel, null);
            if ($registeredSparkMeterModel) {
                $meterModel['syncStatus'] = $meterModelHash === $registeredSparkMeterModel->hash ?
                    SyncStatus::SYNCED : SyncStatus::MODIFIED;
                $relatedMeterType = $meterTypes->find($registeredSparkMeterModel->mpm_meter_type_id);
            } else {
                $meterModel['syncStatus'] = SyncStatus::NOT_REGISTERED_YET;
            }
            $meterModel['hash'] = $meterModelHash;
            $meterModel['relatedMeterType'] = $relatedMeterType;
            $meterModel['registeredSparkMeterModel'] = $registeredSparkMeterModel;

            return $meterModel;
        });

        $meterModelSyncStatus = $sparkMeterModelsCollection->whereNotIn('syncStatus', 1)->count();

        if ($meterModelSyncStatus) {
            return ['result' => false, 'message' => 'meter models are not updated for site '.$siteId];
        } else {
            return ['result' => true, 'message' => 'Records are updated'];
        }
    }

    public function createRelatedMeterModel($meterModel) {
        return $this->meterType->newQuery()->create([
            'online' => 1,
            'phase' => $meterModel['phase_count'],
            'max_current' => $meterModel['continuous_limit'],
        ]);
    }

    public function updateRelatedMeterModel($meterModel, $relatedMeterModel) {
        return $meterModel['relatedMeterModel']->update([
            'phase' => $meterModel['phase_count'],
            'max_current' => $meterModel['continuous_limit'],
        ]);
    }
}

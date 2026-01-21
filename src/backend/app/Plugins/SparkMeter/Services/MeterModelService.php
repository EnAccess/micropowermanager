<?php

namespace App\Plugins\SparkMeter\Services;

use App\Models\Meter\MeterType;
use App\Plugins\SparkMeter\Exceptions\SparkAPIResponseException;
use App\Plugins\SparkMeter\Helpers\SmTableEncryption;
use App\Plugins\SparkMeter\Http\Requests\SparkMeterApiRequests;
use App\Plugins\SparkMeter\Models\SmMeterModel;
use App\Plugins\SparkMeter\Models\SmSite;
use App\Plugins\SparkMeter\Models\SyncStatus;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

/**
 * @implements ISynchronizeService<SmMeterModel>
 */
class MeterModelService implements ISynchronizeService {
    private string $rootUrl = '/meters';

    public function __construct(
        private SparkMeterApiRequests $sparkMeterApiRequests,
        private SmTableEncryption $smTableEncryption,
        private SmMeterModel $smMeterModel,
        private SmSite $smSite,
        private MeterType $meterType,
        private SmSyncSettingService $smSyncSettingService,
        private SmSyncActionService $smSyncActionService,
    ) {}

    /**
     * @return LengthAwarePaginator<int, SmMeterModel>
     */
    public function getSmMeterModels(Request $request): LengthAwarePaginator {
        $perPage = (int) $request->input('per_page', 15);

        return $this->smMeterModel->newQuery()->with(['meterType', 'site.mpmMiniGrid'])->paginate($perPage);
    }

    public function getSmMeterModelsCount(): int {
        return count($this->smMeterModel->newQuery()->get());
    }

    /**
     * @return LengthAwarePaginator<int, SmMeterModel>
     */
    public function sync(): LengthAwarePaginator {
        $synSetting = $this->smSyncSettingService->getSyncSettingsByActionName('MeterModels');
        $syncAction = $this->smSyncActionService->getSyncActionBySynSettingId($synSetting->id);
        try {
            $syncCheck = $this->syncCheck(true);
            $meterModelsCollection = collect($syncCheck)->except('available_site_count');
            $meterModelsCollection->each(function (array $meterModels) {
                $meterModels['site_data']->filter(fn (array $meterModel): bool => $meterModel['syncStatus'] === SyncStatus::NOT_REGISTERED_YET)->each(function (array $meterModel) use ($meterModels) {
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

                $meterModels['site_data']->filter(fn (array $meterModel): bool => $meterModel['syncStatus'] === SyncStatus::MODIFIED)->each(function (array $meterModel) use ($meterModels) {
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
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function syncCheck(bool $returnData = false): array {
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
                    $returnArray[] = ['result' => false];
                }
                throw new SparkAPIResponseException($e->getMessage());
            }
            // @phpstan-ignore argument.templateType,argument.templateType
            $sparkMeterModelsCollection = collect($sparkMeterModels['models']);

            $meterModels = $this->smMeterModel->newQuery()->where('site_id', $site->site_id)->get();
            $meterTypes = $this->meterType->newQuery()->get();

            $sparkMeterModelsCollection->transform(function (array $meterModel) use ($meterModels, $meterTypes): array {
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

            $meterModelSyncStatus = $sparkMeterModelsCollection->whereNotIn('syncStatus', [1])->count();

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

    /**
     * @param array<string, mixed> $model
     */
    public function modelHasher(array $model, ?string ...$params): string {
        return $smModelHash = $this->smTableEncryption->makeHash([
            $model['name'],
            $model['phase_count'],
            $model['continuous_limit'],
            $model['inrush_limit'],
        ]);
    }

    /**
     * @return array{result: bool, message: string}
     */
    public function syncCheckBySite(string $siteId): array {
        try {
            $url = $this->rootUrl.'/models';
            $sparkMeterModels = $this->sparkMeterApiRequests->get($url, $siteId);
        } catch (\Exception $e) {
            Log::critical('Spark meter meter-models sync-check-by-site failed.', ['Error :' => $e->getMessage()]);
            throw new SparkAPIResponseException($e->getMessage());
        }
        // @phpstan-ignore argument.templateType,argument.templateType
        $sparkMeterModelsCollection = collect($sparkMeterModels['models']);

        $meterModels = $this->smMeterModel->newQuery()->where('site_id', $siteId)->get();
        $meterTypes = $this->meterType->newQuery()->get();

        $sparkMeterModelsCollection->transform(function (array $meterModel) use ($meterModels, $meterTypes): array {
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

        $meterModelSyncStatus = $sparkMeterModelsCollection->whereNotIn('syncStatus', [1])->count();

        if ($meterModelSyncStatus) {
            return ['result' => false, 'message' => 'meter models are not updated for site '.$siteId];
        } else {
            return ['result' => true, 'message' => 'Records are updated'];
        }
    }

    /**
     * @param array<string, mixed> $meterModel
     */
    public function createRelatedMeterModel(array $meterModel): MeterType {
        return $this->meterType->newQuery()->create([
            'online' => 1,
            'phase' => $meterModel['phase_count'],
            'max_current' => $meterModel['continuous_limit'],
        ]);
    }

    /**
     * @param array<string, mixed> $meterModel
     */
    public function updateRelatedMeterModel(array $meterModel, mixed $relatedMeterModel): mixed {
        return $meterModel['relatedMeterModel']->update([
            'phase' => $meterModel['phase_count'],
            'max_current' => $meterModel['continuous_limit'],
        ]);
    }
}

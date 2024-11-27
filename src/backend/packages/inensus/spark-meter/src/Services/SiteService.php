<?php

namespace Inensus\SparkMeter\Services;

use App\Models\City;
use App\Models\Cluster;
use App\Models\GeographicalInformation;
use App\Models\MiniGrid;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Inensus\SparkMeter\Exceptions\SparkAPIResponseException;
use Inensus\SparkMeter\Helpers\SmTableEncryption;
use Inensus\SparkMeter\Http\Requests\SparkMeterApiRequests;
use Inensus\SparkMeter\Models\SmSite;
use Inensus\SparkMeter\Models\SyncStatus;

class SiteService implements ISynchronizeService {
    private $site;
    private $sparkMeterApiRequests;
    private $rootUrl = '/organizations';
    private $smTableEncryption;
    private $organizationService;
    private $cluster;
    private $miniGrid;
    private $city;
    private $geographicalInformation;
    private $smSyncSettingService;
    private $smSyncActionService;

    public function __construct(
        SmSite $site,
        SparkMeterApiRequests $sparkMeterApiRequests,
        SmTableEncryption $smTableEncryption,
        OrganizationService $organizationService,
        Cluster $cluster,
        MiniGrid $miniGrid,
        City $city,
        GeographicalInformation $geographicalInformation,
        SmSyncSettingService $smSyncSettingService,
        SmSyncActionService $smSyncActionService,
    ) {
        $this->site = $site;
        $this->sparkMeterApiRequests = $sparkMeterApiRequests;
        $this->smTableEncryption = $smTableEncryption;
        $this->organizationService = $organizationService;
        $this->cluster = $cluster;
        $this->miniGrid = $miniGrid;
        $this->city = $city;
        $this->geographicalInformation = $geographicalInformation;
        $this->smSyncSettingService = $smSyncSettingService;
        $this->smSyncActionService = $smSyncActionService;
    }

    public function getSmSites($request) {
        $perPage = $request->input('per_page') ?? 15;
        $sites = $this->site->newQuery()->with('mpmMiniGrid')->paginate($perPage);

        foreach ($sites as $site) {
            if ($site->thundercloud_token) {
                $data = [
                    'thundercloud_token' => $site->thundercloud_token,
                ];
                $this->update($site->id, $data);
            }
        }

        return $sites;
    }

    public function getSmSitesCount() {
        return count($this->site->newQuery()->get());
    }

    public function creteRelatedMiniGrid($site) {
        $cluster = $this->cluster->newQuery()->latest('created_at')->first();
        $miniGrid = $this->miniGrid->newQuery()->create([
            'name' => $site['name'],
            'cluster_id' => $cluster->id,
        ]);

        $cityName = explode('-', $site['name'])[1].' Village';
        $this->city->newQuery()->create([
            'name' => $cityName,
            'mini_grid_id' => $miniGrid->id,
            'cluster_id' => $miniGrid->cluster_id,
        ]);

        return $miniGrid;
    }

    public function updateGeographicalInformation($miniGridId) {
        $geographicalInformation = $this->geographicalInformation->newQuery()->whereHasMorph(
            'owner',
            [MiniGrid::class],
            static function ($q) use ($miniGridId) {
                $q->where('id', $miniGridId);
            }
        )->first();
        $points = explode(',', config('spark.geoLocation'));
        $latitude = strval(doubleval($points[0]) + (mt_rand(10, 10000) / 10000));
        $longitude = strval(doubleval($points[1]) + (mt_rand(10, 10000) / 10000));
        $points = $latitude.','.$longitude;
        $geographicalInformation->update([
            'points' => $points,
        ]);
    }

    public function updateRelatedMiniGrid($site, $miniGrid) {
        return $miniGrid->newQuery()->update([
            'name' => $site['name'],
        ]);
    }

    public function update($siteId, $data) {
        $site = $this->site->newQuery()->find($siteId);
        $site->update([
            'thundercloud_token' => $data['thundercloud_token'],
        ]);

        try {
            $rootUrl = '/system-info';
            $result = $this->sparkMeterApiRequests->get($rootUrl, $site->site_id);

            $system = $result['grids'][0];

            $site->is_authenticated = true;
            $site->is_online = Carbon::parse($system['last_sync_date'])
                ->toDateTimeString() > Carbon::now()->utc()
                ->subMinutes(15)->toDateTimeString();
        } catch (\Exception $e) {
            $site->is_authenticated = false;
            $site->is_online = false;
        }
        $site->update();

        return $site->fresh();
    }

    public function getThunderCloudInformation($siteId) {
        return $this->site->newQuery()->where('site_id', $siteId)->first();
    }

    public function checkLocationAvailability() {
        return $this->cluster->newQuery()->latest('created_at')->first();
    }

    public function sync() {
        $synSetting = $this->smSyncSettingService->getSyncSettingsByActionName('Sites');
        $syncAction = $this->smSyncActionService->getSyncActionBySynSettingId($synSetting->id);
        try {
            $syncCheck = $this->syncCheck(true);
            $syncCheck['data']->filter(function ($site) {
                return $site['syncStatus'] === SyncStatus::NOT_REGISTERED_YET;
            })->each(function ($site) {
                $miniGrid = $this->creteRelatedMiniGrid($site);
                $this->site->newQuery()->create([
                    'site_id' => $site['id'],
                    'mpm_mini_grid_id' => $miniGrid->id,
                    'thundercloud_url' => $site['thundercloud_url'].'api/v0',
                    'hash' => $site['hash'],
                ]);
                $this->updateGeographicalInformation($miniGrid->id);
            });

            $syncCheck['data']->filter(function ($site) {
                return $site['syncStatus'] === SyncStatus::MODIFIED;
            })->each(function ($site) {
                $miniGrid = is_null($site['relatedMiniGrid']) ?
                    $this->creteRelatedMiniGrid($site) : $this->updateRelatedMiniGrid(
                        $site,
                        $site['relatedMiniGrid']
                    );
                $this->updateGeographicalInformation($miniGrid->id);
                $site['registeredSparkSite']->update([
                    'site_id' => $site['id'],
                    'thundercloud_url' => $site['thundercloud_url'].'api/v0',
                    'mpm_mini_grid_id' => $miniGrid->id,
                    'hash' => $site['hash'],
                ]);
            });
            $this->smSyncActionService->updateSyncAction($syncAction, $synSetting, true);

            return $this->site->newQuery()->with('mpmMiniGrid')->paginate(config('spark.paginate'));
        } catch (\Exception $e) {
            $this->smSyncActionService->updateSyncAction($syncAction, $synSetting, false);
            Log::critical('Spark sites sync failed.', ['Error :' => $e->getMessage()]);
            throw new \Exception($e->getMessage());
        }
    }

    public function syncCheck($returnData = false) {
        $organizations = $this->organizationService->getOrganizations();
        $sites = [];
        try {
            foreach ($organizations as $organization) {
                $url = $this->rootUrl.'/'.$organization->organization_id.'/sites';
                $result = $this->sparkMeterApiRequests->getFromKoios($url);
                $organizationSites = $result['sites'];
                foreach ($organizationSites as $site) {
                    array_push($sites, $site);
                }
            }
        } catch (SparkAPIResponseException $e) {
            Log::critical('Spark meter sites sync-check failed.', ['Error :' => $e->getMessage()]);
            if ($returnData) {
                return ['result' => false];
            }
            throw new SparkAPIResponseException($e->getMessage());
        }
        $sitesCollection = collect($sites);
        $sparkSites = $this->site->newQuery()->get();
        $miniGrids = $this->miniGrid->newQuery()->get();

        $sitesCollection->transform(function ($site) use ($sparkSites, $miniGrids) {
            $registeredSparkSite = $sparkSites->firstWhere('site_id', $site['id']);
            $relatedMiniGrid = null;
            $siteHash = $this->modelHasher($site, null);
            if ($registeredSparkSite) {
                $site['syncStatus'] = $siteHash === $registeredSparkSite->hash ?
                    SyncStatus::SYNCED : SyncStatus::MODIFIED;
                $relatedMiniGrid = $miniGrids->find($registeredSparkSite->mpm_mini_grid_id);
            } else {
                $site['syncStatus'] = SyncStatus::NOT_REGISTERED_YET;
            }
            $site['hash'] = $siteHash;
            $site['relatedMiniGrid'] = $relatedMiniGrid;
            $site['registeredSparkSite'] = $registeredSparkSite;

            return $site;
        });
        $siteSyncStatus = $sitesCollection->whereNotIn('syncStatus', 1)->count();
        if ($siteSyncStatus) {
            return $returnData ? ['data' => $sitesCollection, 'result' => false] : ['result' => false];
        }

        return $returnData ? ['data' => $sitesCollection, 'result' => true] : ['result' => true];
    }

    public function modelHasher($model, ...$params): string {
        return $this->smTableEncryption->makeHash([
            $model['name'],
            $model['display_name'],
            $model['thundercloud_url'],
        ]);
    }

    public function syncCheckBySite($siteId) {
        // This function is not using for sites
    }
}

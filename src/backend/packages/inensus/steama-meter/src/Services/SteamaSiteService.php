<?php

namespace Inensus\SteamaMeter\Services;

use App\Models\City;
use App\Models\Cluster;
use App\Models\GeographicalInformation;
use App\Models\MiniGrid;
use Illuminate\Support\Facades\Log;
use Inensus\SteamaMeter\Exceptions\SteamaApiResponseException;
use Inensus\SteamaMeter\Helpers\ApiHelpers;
use Inensus\SteamaMeter\Http\Clients\SteamaMeterApiClient;
use Inensus\SteamaMeter\Models\SteamaSite;
use Inensus\SteamaMeter\Models\SyncStatus;

class SteamaSiteService implements ISynchronizeService {
    private $site;
    private $steamaApi;
    private $apiHelpers;
    private $rootUrl = '/sites';
    private $miniGrid;
    private $cluster;
    private $geographicalInformation;
    private $city;
    private $steamaSyncSettingService;
    private $steamaSyncActionService;

    public function __construct(
        SteamaSite $steamaSiteModel,
        SteamaMeterApiClient $steamaApi,
        ApiHelpers $apiHelpers,
        MiniGrid $miniGrid,
        Cluster $cluster,
        GeographicalInformation $geographicalInformation,
        City $city,
        SteamaSyncSettingService $steamaSyncSettingService,
        StemaSyncActionService $steamaSyncActionService,
    ) {
        $this->site = $steamaSiteModel;
        $this->steamaApi = $steamaApi;
        $this->apiHelpers = $apiHelpers;
        $this->miniGrid = $miniGrid;
        $this->cluster = $cluster;
        $this->city = $city;
        $this->geographicalInformation = $geographicalInformation;
        $this->steamaSyncSettingService = $steamaSyncSettingService;
        $this->steamaSyncActionService = $steamaSyncActionService;
    }

    public function getSites($request) {
        $perPage = $request->input('per_page') ?? 15;

        return $this->site->newQuery()->with('mpmMiniGrid.location')->paginate($perPage);
    }

    public function getSitesCount() {
        return count($this->site->newQuery()->get());
    }

    public function sync() {
        $synSetting = $this->steamaSyncSettingService->getSyncSettingsByActionName('Sites');
        $syncAction = $this->steamaSyncActionService->getSyncActionBySynSettingId($synSetting->id);
        try {
            $syncCheck = $this->syncCheck(true);
            $syncCheck['data']->filter(function ($value) {
                return $value['syncStatus'] === SyncStatus::NOT_REGISTERED_YET;
            })->each(function ($site) {
                $miniGrid = $this->creteRelatedMiniGrid($site);
                $this->site->newQuery()->create([
                    'site_id' => $site['id'],
                    'mpm_mini_grid_id' => $miniGrid->id,
                    'hash' => $site['hash'],
                ]);
                $this->createOrUpdateGeographicalInformation($miniGrid->id, $site);
            });

            $syncCheck['data']->filter(function ($value) {
                return $value['syncStatus'] === SyncStatus::MODIFIED;
            })->each(function ($site) {
                $miniGrid = is_null($site['relatedMiniGrid']) ?
                    $this->creteRelatedMiniGrid($site) : $this->updateRelatedMiniGrid($site, $site['relatedMiniGrid']);
                $this->createOrUpdateGeographicalInformation($miniGrid->id, $site);
                $site['registeredStmSite']->update([
                    'site_id' => $site['id'],
                    'mpm_mini_grid_id' => $miniGrid->id,
                    'hash' => $site['hash'],
                ]);
            });
            $this->steamaSyncActionService->updateSyncAction($syncAction, $synSetting, true);

            return $this->site->newQuery()->with('mpmMiniGrid.location')->paginate(config('steama.paginate'));
        } catch (\Exception $e) {
            $this->steamaSyncActionService->updateSyncAction($syncAction, $synSetting, false);
            Log::critical('Steama sites sync failed.', ['Error :' => $e->getMessage()]);
            throw new \Exception($e->getMessage());
        }
    }

    public function syncCheck($returnData = false) {
        try {
            $url = $this->rootUrl.'?page=1&page_size=100';
            $result = $this->steamaApi->get($url);

            $sites = $result['results'];

            while ($result['next']) {
                $url = $this->rootUrl.'?'.explode('?', $result['next'])[1];
                $result = $this->steamaApi->get($url);
                foreach ($result['results'] as $site) {
                    array_push($sites, $site);
                }
            }
        } catch (SteamaApiResponseException $e) {
            if ($returnData) {
                return ['result' => false];
            }
            throw new SteamaApiResponseException($e->getMessage());
        }
        $sitesCollection = collect($sites);
        $stmSites = $this->site->newQuery()->get();
        $miniGrids = $this->miniGrid->newQuery()->get();

        $sitesCollection->transform(function ($site) use ($stmSites, $miniGrids) {
            $registeredStmSite = $stmSites->firstWhere('site_id', $site['id']);
            $relatedMiniGrid = null;
            $siteHash = $this->steamaSiteHasher($site);
            if ($registeredStmSite) {
                $site['syncStatus'] = $siteHash === $registeredStmSite->hash ?
                    SyncStatus::SYNCED : SyncStatus::MODIFIED;
                $relatedMiniGrid = $miniGrids->find($registeredStmSite->mpm_mini_grid_id);
            } else {
                $site['syncStatus'] = SyncStatus::NOT_REGISTERED_YET;
            }
            $site['hash'] = $siteHash;
            $site['relatedMiniGrid'] = $relatedMiniGrid;
            $site['registeredStmSite'] = $registeredStmSite;

            return $site;
        });

        $siteSyncStatus = $sitesCollection->whereNotIn('syncStatus', SyncStatus::SYNCED)->count();
        if ($siteSyncStatus) {
            return $returnData ? ['data' => $sitesCollection, 'result' => false] : ['result' => false];
        }

        return $returnData ? ['data' => $sitesCollection, 'result' => true] : ['result' => true];
    }

    public function creteRelatedMiniGrid($site) {
        $cluster = $this->cluster->newQuery()->latest('created_at')->first();
        $miniGrid = $this->miniGrid->newQuery()->create([
            'name' => $site['name'],
            'cluster_id' => $cluster->id,
        ]);
        $this->city->newQuery()->create([
            'name' => $miniGrid->name.' Village',
            'mini_grid_id' => $miniGrid->id,
            'cluster_id' => $miniGrid->cluster->id,
        ]);

        return $miniGrid;
    }

    public function updateRelatedMiniGrid($site, $miniGrid) {
        $miniGrid->update([
            'name' => $site['name'],
        ]);

        return $miniGrid->fresh();
    }

    public function createOrUpdateGeographicalInformation($miniGridId, $site) {
        $geographicalInformation = $this->geographicalInformation->newQuery()->whereHasMorph(
            'owner',
            [MiniGrid::class],
            static function ($q) use ($miniGridId) {
                $q->where('id', $miniGridId);
            }
        )->first();
        $points = $site['latitude'] === null ?
            config('steama.geoLocation') : $site['latitude'].','.$site['longitude'];

        if ($geographicalInformation) {
            $geographicalInformation->update([
                'points' => $points,
            ]);
        } else {
            $this->geographicalInformation->create([
                'owner_type' => 'mini-grid',
                'owner_id' => $miniGridId,
                'points' => $points,
            ]);
        }
    }

    public function checkLocationAvailability() {
        return $this->cluster->newQuery()->latest('created_at')->first();
    }

    private function steamaSiteHasher($steamaSite) {
        return $this->apiHelpers->makeHash([
            $steamaSite['name'],
            $steamaSite['latitude'],
            $steamaSite['longitude'],
            $steamaSite['num_meters'],
        ]);
    }
}

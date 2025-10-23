<?php

namespace Inensus\SparkMeter\Services;

use App\Models\City;
use App\Models\Cluster;
use App\Models\GeographicalInformation;
use App\Models\MiniGrid;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Inensus\SparkMeter\Exceptions\SparkAPIResponseException;
use Inensus\SparkMeter\Helpers\SmTableEncryption;
use Inensus\SparkMeter\Http\Requests\SparkMeterApiRequests;
use Inensus\SparkMeter\Models\SmSite;
use Inensus\SparkMeter\Models\SyncStatus;

/**
 * @implements ISynchronizeService<SmSite>
 */
class SiteService implements ISynchronizeService {
    private string $rootUrl = '/organizations';

    public function __construct(
        private SmSite $site,
        private SparkMeterApiRequests $sparkMeterApiRequests,
        private SmTableEncryption $smTableEncryption,
        private OrganizationService $organizationService,
        private Cluster $cluster,
        private MiniGrid $miniGrid,
        private City $city,
        private GeographicalInformation $geographicalInformation,
        private SmSyncSettingService $smSyncSettingService,
        private SmSyncActionService $smSyncActionService,
    ) {}

    /**
     * @return LengthAwarePaginator<int, SmSite>
     */
    public function getSmSites(Request $request): LengthAwarePaginator {
        $perPage = (int) $request->input('per_page', 15);
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

    public function getSmSitesCount(): int {
        return count($this->site->newQuery()->get());
    }

    /**
     * @param array<string, mixed> $site
     */
    public function creteRelatedMiniGrid(array $site): MiniGrid {
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

    public function updateGeographicalInformation(int $miniGridId): void {
        $geographicalInformation = $this->geographicalInformation->newQuery()->whereHasMorph(
            'owner',
            [MiniGrid::class],
            static function ($q) use ($miniGridId) {
                $q->where('id', $miniGridId);
            }
        )->first();
        $points = explode(',', config('spark.geoLocation'));
        $latitude = strval(floatval($points[0]) + (mt_rand(10, 10000) / 10000));
        $longitude = strval(floatval($points[1]) + (mt_rand(10, 10000) / 10000));
        $points = $latitude.','.$longitude;
        $geographicalInformation->update([
            'points' => $points,
        ]);
    }

    /**
     * @param array<string, mixed> $site
     */
    public function updateRelatedMiniGrid(array $site, MiniGrid $miniGrid): int {
        return $miniGrid->newQuery()->update([
            'name' => $site['name'],
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $siteId, array $data): ?SmSite {
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
        } catch (\Exception) {
            $site->is_authenticated = false;
            $site->is_online = false;
        }
        $site->update();

        return $site->fresh();
    }

    public function getThunderCloudInformation(string $siteId): ?SmSite {
        return $this->site->newQuery()->where('site_id', $siteId)->first();
    }

    public function checkLocationAvailability(): ?Cluster {
        return $this->cluster->newQuery()->latest('created_at')->first();
    }

    /**
     * @return LengthAwarePaginator<int, SmSite>
     */
    public function sync(): LengthAwarePaginator {
        $synSetting = $this->smSyncSettingService->getSyncSettingsByActionName('Sites');
        $syncAction = $this->smSyncActionService->getSyncActionBySynSettingId($synSetting->id);
        try {
            $syncCheck = $this->syncCheck(true);
            $syncCheck['data']->filter(fn (array $site): bool => $site['syncStatus'] === SyncStatus::NOT_REGISTERED_YET)->each(function (array $site) {
                $miniGrid = $this->creteRelatedMiniGrid($site);
                $this->site->newQuery()->create([
                    'site_id' => $site['id'],
                    'mpm_mini_grid_id' => $miniGrid->id,
                    'thundercloud_url' => $site['thundercloud_url'].'api/v0',
                    'hash' => $site['hash'],
                ]);
                $this->updateGeographicalInformation($miniGrid->id);
            });

            $syncCheck['data']->filter(fn (array $site): bool => $site['syncStatus'] === SyncStatus::MODIFIED)->each(function (array $site) {
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
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function syncCheck(bool $returnData = false): array {
        $organizations = $this->organizationService->getOrganizations();
        $sites = [];
        try {
            foreach ($organizations as $organization) {
                $url = $this->rootUrl.'/'.$organization->organization_id.'/sites';
                $result = $this->sparkMeterApiRequests->getFromKoios($url);
                $organizationSites = $result['sites'];
                foreach ($organizationSites as $site) {
                    $sites[] = $site;
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

        $sitesCollection->transform(function (array $site) use ($sparkSites, $miniGrids): array {
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
        $siteSyncStatus = $sitesCollection->whereNotIn('syncStatus', [1])->count();
        if ($siteSyncStatus) {
            return $returnData ? ['data' => $sitesCollection, 'result' => false] : ['result' => false];
        }

        return $returnData ? ['data' => $sitesCollection, 'result' => true] : ['result' => true];
    }

    /**
     * @param array<string, mixed> $model
     */
    public function modelHasher(array $model, ?string ...$params): string {
        return $this->smTableEncryption->makeHash([
            $model['name'],
            $model['display_name'],
            $model['thundercloud_url'],
        ]);
    }

    /**
     * @return array{result: bool, message: string}
     */
    public function syncCheckBySite(string $siteId): array {
        // This function is not using for sites
        throw new \Exception('Method syncCheckBySite() not yet implemented.');
    }
}

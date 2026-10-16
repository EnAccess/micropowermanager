<?php

namespace App\Plugins\SparkMeter\Tests\Feature;

use App\Models\City;
use App\Models\Cluster;
use App\Models\GeographicalInformation;
use App\Models\MiniGrid;
use App\Plugins\SparkMeter\Models\SmCredential;
use App\Plugins\SparkMeter\Models\SmOrganization;
use App\Plugins\SparkMeter\Models\SmSite;
use App\Plugins\SparkMeter\Services\SiteService;
use App\Plugins\SparkMeter\Services\SmSyncSettingService;
use Database\Factories\ClusterFactory;
use Database\Factories\UserFactory;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class SiteSyncTest extends TestCase {
    private const string ORGANIZATION_ID = 'org-1';
    private const string SITE_ID = 'site-1';

    private Cluster $cluster;

    protected function setUp(): void {
        parent::setUp();
        $this->cluster = ClusterFactory::new()->create(['manager_id' => UserFactory::new()->create()->id]);
        resolve(SmSyncSettingService::class)->createDefaultSettings();
        SmCredential::query()->create([
            'api_url' => 'https://koios.test/api/v0',
            'api_key' => Crypt::encryptString('key'),
            'api_secret' => Crypt::encryptString('secret'),
            'is_authenticated' => 1,
        ]);
        SmOrganization::query()->create([
            'organization_id' => self::ORGANIZATION_ID,
            'code' => 'ORG',
            'display_name' => 'Org',
        ]);
    }

    private function fakeKoiosSites(string $siteName): void {
        $sites = [
            'error' => null,
            'sites' => [[
                'id' => self::SITE_ID,
                'name' => $siteName,
                'display_name' => $siteName,
                'thundercloud_url' => 'https://site-1.sparkmeter.cloud/',
            ]],
        ];
        $handler = HandlerStack::create(new MockHandler([
            new Response(200, [], (string) json_encode($sites)),
        ]));
        $this->app->instance(Client::class, new Client(['handler' => $handler]));
    }

    public function testSyncCreatesMiniGridCityAndLocationForANewSite(): void {
        $this->fakeKoiosSites('Ground');

        resolve(SiteService::class)->sync();

        $site = SmSite::query()->where('site_id', self::SITE_ID)->firstOrFail();
        $this->assertSame('https://site-1.sparkmeter.cloud/api/v0', $site->thundercloud_url);
        $miniGrid = MiniGrid::query()->with('location')->findOrFail($site->mpm_mini_grid_id);
        $this->assertSame('Ground', $miniGrid->name);
        $this->assertSame($this->cluster->id, $miniGrid->cluster_id);
        $this->assertInstanceOf(GeographicalInformation::class, $miniGrid->location);
        $city = City::query()->where('mini_grid_id', $miniGrid->id)->firstOrFail();
        $this->assertSame('Ground Village', $city->name);
    }

    public function testSyncRenamesOnlyTheMiniGridOfAModifiedSite(): void {
        $siteMiniGrid = MiniGrid::query()->create(['name' => 'Old name', 'cluster_id' => $this->cluster->id]);
        $otherMiniGrid = MiniGrid::query()->create(['name' => 'Other grid', 'cluster_id' => $this->cluster->id]);
        SmSite::query()->create([
            'site_id' => self::SITE_ID,
            'mpm_mini_grid_id' => $siteMiniGrid->id,
            'thundercloud_url' => 'https://site-1.sparkmeter.cloud/api/v0',
            'hash' => 'stale',
        ]);
        $this->fakeKoiosSites('New name');

        resolve(SiteService::class)->sync();

        $this->assertSame('New name', $siteMiniGrid->fresh()->name);
        $this->assertSame('Other grid', $otherMiniGrid->fresh()->name);
    }
}

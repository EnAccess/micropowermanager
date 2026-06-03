<?php

namespace App\Plugins\SteamaMeter\Tests\Unit;

use App\Models\City;
use App\Plugins\SteamaMeter\Exceptions\SteamaApiResponseException;
use App\Plugins\SteamaMeter\Http\Clients\SteamaMeterApiClient;
use App\Plugins\SteamaMeter\Models\SteamaSite;
use App\Plugins\SteamaMeter\Services\SteamaSiteService;
use Database\Factories\ClusterFactory;
use Database\Factories\UserFactory;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Tests\TestCase;

class SteamaSiteServiceTest extends TestCase {
    use MockeryPHPUnitIntegration;

    public function testSyncRethrowsTheOriginalApiExceptionWithoutWrapping(): void {
        $this->mock(SteamaMeterApiClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getAllResults')
                ->andThrow(new SteamaApiResponseException('Steama API unavailable'));
        });

        $this->expectException(SteamaApiResponseException::class);
        $this->expectExceptionMessage('Steama API unavailable');

        app(SteamaSiteService::class)->sync();
    }

    public function testSyncCreatesMiniGridSiteAndCityForANewSite(): void {
        ClusterFactory::new()->create(['manager_id' => UserFactory::new()->create()->id]);

        $this->mock(SteamaMeterApiClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getAllResults')->andReturn([
                ['id' => 501, 'name' => 'Calabar', 'latitude' => '4.95', 'longitude' => '8.32', 'num_meters' => 3],
            ]);
        });

        app(SteamaSiteService::class)->sync();

        $this->assertTrue(SteamaSite::query()->where('site_id', 501)->exists());
        $this->assertTrue(City::query()->where('name', 'Calabar Village')->exists());
    }
}

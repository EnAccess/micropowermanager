<?php

namespace App\Plugins\SparkMeter\Tests\Feature;

use App\Models\City;
use App\Models\MiniGrid;
use App\Plugins\SparkMeter\Models\SmSite;
use App\Plugins\SparkMeter\Models\SmTariff;
use Database\Factories\ClusterFactory;
use Database\Factories\TariffFactory;
use Database\Factories\UserFactory;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class SmTariffUpdateInfoTest extends TestCase {
    private const string SITE_ID = 'site-77';

    /**
     * SparkMeter's PUT /tariff/:id is a full overwrite: any field not present in
     * the request is erased on SparkMeter's side. The controller only receives
     * name/flatPrice/flatLoadLimit/planEnabled/touEnabled from the caller (the
     * only fields SmTariffRequest actually requires), so it must carry forward
     * every other current field (daily-energy-limit, plan pricing) from the
     * tariff's existing remote state rather than sending them as null.
     */
    public function testUpdateInfoPreservesFieldsNotIncludedInTheRequest(): void {
        $cluster = ClusterFactory::new()->create(['manager_id' => UserFactory::new()->create()->id]);
        $miniGrid = MiniGrid::query()->create(['name' => 'Test Grid', 'cluster_id' => $cluster->id]);
        City::query()->create(['name' => 'Test City', 'mini_grid_id' => $miniGrid->id, 'country_id' => 0]);
        SmSite::query()->create([
            'site_id' => self::SITE_ID,
            'mpm_mini_grid_id' => $miniGrid->id,
            'thundercloud_url' => 'https://site77.sparkmeter.cloud/api/v0',
            'thundercloud_token' => 'test-token',
            'is_authenticated' => 1,
            'is_online' => 1,
        ]);
        $mpmTariff = TariffFactory::new()->create();
        SmTariff::query()->create([
            'site_id' => self::SITE_ID,
            'tariff_id' => 'tariff-1',
            'mpm_tariff_id' => $mpmTariff->id,
            'flat_load_limit' => 30,
            'plan_duration' => '1m',
            'plan_price' => 100,
            'hash' => 'irrelevant',
        ]);

        $currentRemoteTariff = [
            'tariff' => [
                'id' => 'tariff-1',
                'name' => $mpmTariff->name,
                'flat_price' => 1000,
                'flat_load_limit' => 30,
                'daily_energy_limit_enabled' => true,
                'daily_energy_limit_value' => 5000,
                'daily_energy_limit_reset_hour' => 6,
                'tou_enabled' => false,
                'tous' => [],
                'plan_enabled' => true,
                'plan_duration' => '1m',
                'plan_price' => 100,
                'plan_fixed_fee' => 50,
            ],
            'error' => null,
            'status' => 'success',
        ];

        $history = [];
        $mockHandler = new MockHandler([
            new Response(200, [], (string) json_encode($currentRemoteTariff)),
            new Response(200, [], (string) json_encode($currentRemoteTariff)),
        ]);
        $handlerStack = HandlerStack::create($mockHandler);
        $handlerStack->push(Middleware::history($history));
        $this->app->instance(Client::class, new Client(['handler' => $handlerStack]));

        $response = $this->actingAs(UserFactory::new()->create())->putJson('/api/spark-meters/sm-tariff', [
            'id' => 'tariff-1',
            'name' => $mpmTariff->name,
            'flatPrice' => 15,
            'flatLoadLimit' => 30,
            'planEnabled' => true,
            'touEnabled' => false,
        ]);

        $response->assertStatus(200);

        // First recorded request is the GET (getSparkTariffInfo), second is the PUT.
        $this->assertCount(2, $history);
        $putRequestBody = json_decode((string) $history[1]['request']->getBody(), true);

        $this->assertSame(true, $putRequestBody['daily_energy_limit_enabled']);
        $this->assertSame(5000, $putRequestBody['daily_energy_limit_value']);
        $this->assertSame(6, $putRequestBody['daily_energy_limit_reset_hour']);
        $this->assertSame(100, $putRequestBody['plan_price']);
        $this->assertSame(50, $putRequestBody['plan_fixed_fee']);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Address\Address;
use App\Models\GeographicalInformation;
use App\Models\MiniGrid;
use Carbon\Carbon;
use Database\Factories\BatteryFactory;
use Database\Factories\CityFactory;
use Database\Factories\ClusterFactory;
use Database\Factories\CompanyDatabaseFactory;
use Database\Factories\CompanyFactory;
use Database\Factories\ConnectionTypeFactory;
use Database\Factories\ManufacturerFactory;
use Database\Factories\MeterFactory;
use Database\Factories\MeterParameterFactory;
use Database\Factories\MeterTariffFactory;
use Database\Factories\MeterTokenFactory;
use Database\Factories\MeterTypeFactory;
use Database\Factories\MiniGridFactory;
use Database\Factories\PaymentHistoryFactory;
use Database\Factories\PersonFactory;
use Database\Factories\SolarFactory;
use Database\Factories\TransactionFactory;
use Database\Factories\UserFactory;
use Database\Factories\VodacomTransactionFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class MiniGridTest extends TestCase
{
    use RefreshMultipleDatabases, WithFaker;

    private $user, $company, $companyDatabase, $person, $clusterIds = [], $miniGridIds = [];

    public function test_user_gets_mini_grid_list()
    {
        $clusterCount = 1;
        $miniGridCount = 2;
        $this->createTestData($clusterCount, $miniGridCount);
        $response = $this->actingAs($this->user)->get('/api/mini-grids');
        $response->assertStatus(200);
        $this->assertEquals(count($response['data']),count($this->miniGridIds));
    }

    public function test_user_gets_mini_grids_for_data_stream()
    {
        $clusterCount = 1;
        $miniGridCount = 2;
        $this->createTestData($clusterCount, $miniGridCount);
        $response = $this->actingAs($this->user)->get('/api/mini-grids?data_stream=1');
        $response->assertStatus(200);
        $this->assertEquals(count($response['data']),0);
    }

    public function test_user_gets_mini_grid_by_id()
    {
        $clusterCount = 1;
        $miniGridCount = 2;
        $this->createTestData($clusterCount, $miniGridCount);
        $response = $this->actingAs($this->user)->get(sprintf('/api/mini-grids/%s', $this->miniGridIds[0]));
        $response->assertStatus(200);
        $this->assertEquals($response['data']['id'],$this->miniGridIds[0]);
    }

    public function test_user_gets_mini_grid_by_id_with_geographical_information(){
        $clusterCount = 1;
        $miniGridCount = 2;
        $this->createTestData($clusterCount, $miniGridCount);
        $response = $this->actingAs($this->user)->get(sprintf('/api/mini-grids/%s?relation=1', $this->miniGridIds[0]));
        $response->assertStatus(200);
        $this->assertEquals(array_key_exists('location',$response['data']),true);
    }

    public function test_user_creates_new_mini_grid()
    {
        $clusterCount = 1;
        $miniGridCount = 0;
        $this->createTestData($clusterCount, $miniGridCount);
        $miGridData =[
            'cluster_id' => $this->clusterIds[0],
            'name' => $this->faker->name,
            'geo_data' => [
                'latitude' => $this->faker->latitude,
                'longitude' => $this->faker->longitude
            ]
        ];
        $response = $this->actingAs($this->user)->post('/api/mini-grids', $miGridData);
        $response->assertStatus(201);
        $this->assertEquals($response['data']['name'],$miGridData['name']);
        $this->assertEquals(count(MiniGrid::query()->get()),1);

    }

    public function test_user_updates_a_mini_grid()
    {
        $clusterCount = 1;
        $miniGridCount = 1;
        $this->createTestData($clusterCount, $miniGridCount);
        $miniGrid = MiniGrid::query()->first();
        $miGridData =[
            'data_stream' => 1,
            'name' => 'updatedName'
        ];
        $response = $this->actingAs($this->user)->put(sprintf('/api/mini-grids/%s',$miniGrid->id), $miGridData);
        $response->assertStatus(200);
        $this->assertEquals($response['data']['name'],$miGridData['name']);
    }

    public function test_user_gets_battery_readings_for_mini_grid()
    {
        $clusterCount = 1;
        $miniGridCount = 1;
        $this->createTestData($clusterCount, $miniGridCount);
        $batteryReading = 10;

        while ($batteryReading > 0) {
            BatteryFactory::new()->create();
            $batteryReading--;
        }

        $response = $this->actingAs($this->user)->get(sprintf('/api/mini-grids/%s/batteries', $this->miniGridIds[0]));
        $response->assertStatus(200);
        $this->assertEquals(count($response['data']),10);
    }

    public function test_user_gets_solar_readings_for_mini_grid()
    {
        $clusterCount = 1;
        $miniGridCount = 1;
        $this->createTestData($clusterCount, $miniGridCount);
        $solarReading = 10;

        while ($solarReading > 0) {
            SolarFactory::new()->create();
            $solarReading--;
        }

        $response = $this->actingAs($this->user)->get(sprintf('/api/mini-grids/%s/solar', $this->miniGridIds[0]));
        $response->assertStatus(200);
    }

    protected function createTestData($clusterCount = 1, $miniGridCount = 1)
    {
        $this->user = UserFactory::new()->create();
        $this->company = CompanyFactory::new()->create();
        $this->companyDatabase = CompanyDatabaseFactory::new()->create();


        while ($clusterCount > 0) {
            $user = UserFactory::new()->create();
            $cluster = ClusterFactory::new()->create([
                'name' => $this->faker->unique()->companySuffix,
                'manager_id' => $this->user->id,
            ]);
            array_push($this->clusterIds, $cluster->id);

            while ($miniGridCount > 0) {
                $geographicalInformation = GeographicalInformation::query()->make(['points' => '111,222']);
                $miniGrid = MiniGridFactory::new()->create([
                    'cluster_id' => $cluster->id,
                    'name' => $this->faker->unique()->companySuffix,
                    'data_stream'=>0
                ]);
                $geographicalInformation->owner()->associate($miniGrid);
                $geographicalInformation->save();
                $city = CityFactory::new()->create([
                    'name' => $this->faker->unique()->citySuffix,
                    'country_id' => 1,
                    'mini_grid_id' => $miniGrid->id,
                    'cluster_id' => $cluster->id,
                ]);
                array_push($this->miniGridIds, $miniGrid->id);
                $miniGridCount--;
            }
            $clusterCount--;

        }
    }

    public function actingAs($user, $driver = null)
    {
        $token = JWTAuth::fromUser($user);
        $this->withHeader('Authorization', "Bearer {$token}");
        parent::actingAs($user);

        return $this;
    }

    protected function generateUniqueNumber(): int
    {
        return ($this->faker->unique()->randomNumber() + $this->faker->unique()->randomNumber() +
            $this->faker->unique()->randomNumber());
    }
}

<?php

namespace Tests\Feature;

use App\Models\Address\Address;
use App\Models\Cluster;
use App\Models\GeographicalInformation;
use Carbon\Carbon;
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
use Database\Factories\TransactionFactory;
use Database\Factories\UserFactory;
use Database\Factories\VodacomTransactionFactory;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ClusterTest extends TestCase
{
    use RefreshMultipleDatabases;
    use WithFaker;

    private $user;
    private $company;
    private $city;
    private $connectionType;
    private $manufacturer;
    private $meterType;
    private $meter;
    private $meterParameter;
    private $meterTariff;
    private $person;
    private $token;
    private $transaction;
    private $clusterIds = [];

    public function testUserGetsClusterListForDashboard()
    {
        $clusterCount = 1;
        $meterCount = 2;
        $transactionCount = 3;
        $this->createTestData($clusterCount, $meterCount, $transactionCount);
        $response = $this->actingAs($this->user)->get('/api/clusters');
        $response->assertStatus(200);
        $this->assertEquals(count($response['data']), $clusterCount);
        $this->assertEquals($response['data'][0]['population'], $meterCount);
        $this->assertEquals($response['data'][0]['clusterData']['meterCount'], $meterCount);
    }

    public function testUserGetsClusterByIdForDashboard()
    {
        $this->createTestData();
        $response = $this->actingAs($this->user)->get(sprintf('/api/clusters/%s', $this->clusterIds[0]));
        $response->assertStatus(200);
        $this->assertEquals($response['data']['population'], 1);
        $this->assertEquals($response['data']['meterCount'], 1);
    }

    public function testUserGetsClusterGeoDataById()
    {
        $this->createTestData();
        $response = $this->actingAs($this->user)->get(sprintf('/api/clusters/%s/geo', $this->clusterIds[0]));
        $response->assertStatus(200);
    }

    public function testUserAddsNewCluster()
    {
        $this->createTestData();
        $clusterData = [
            'name' => 'test cluster',
            'geo_type' => 'manual',
            'geo_data' => '{}',
            'manager_id' => $this->user->id,
        ];
        $response = $this->actingAs($this->user)->post('/api/clusters', $clusterData);
        $response->assertStatus(200);
        $this->assertEquals(Cluster::query()->count(), count($this->clusterIds) + 1);
    }

    protected function createTestData($clusterCount = 1, $meterCount = 1, $transactionCount = 1)
    {
        $this->user = UserFactory::new()->create();
        $this->city = CityFactory::new()->create();
        $this->company = CompanyFactory::new()->create();
        $this->companyDatabase = CompanyDatabaseFactory::new()->create();
        $this->manufacturer = ManufacturerFactory::new()->create();
        $this->meterType = MeterTypeFactory::new()->create();
        $this->meterTariff = MeterTariffFactory::new()->create();
        $this->connectionType = ConnectionTypeFactory::new()->create();
        $this->connectionGroup = ConnectionTypeFactory::new()->create();
        $meterCountClone = $meterCount;
        $transactionCountClone = $transactionCount;

        while ($clusterCount > 0) {
            $user = UserFactory::new()->create();
            $cluster = ClusterFactory::new()->create([
                'name' => $this->faker->unique()->companySuffix,
                'manager_id' => $this->user->id,
            ]);
            array_push($this->clusterIds, $cluster->id);
            $miniGrid = MiniGridFactory::new()->create([
                'cluster_id' => $cluster->id,
                'name' => $this->faker->unique()->companySuffix,
            ]);
            $city = CityFactory::new()->create([
                'name' => $this->faker->unique()->citySuffix,
                'country_id' => 1,
                'mini_grid_id' => $miniGrid->id,
                'cluster_id' => $cluster->id,
            ]);
            --$clusterCount;

            while ($meterCount > 0) {
                $meter = MeterFactory::new()->create([
                    'meter_type_id' => $this->meterType->id,
                    'in_use' => true,
                    'manufacturer_id' => 1,
                    'serial_number' => str_random(36),
                ]);
                $geographicalInformation = GeographicalInformation::query()->make(['points' => '111,222']);
                $person = PersonFactory::new()->create();
                $meterAddressData = [
                    'city_id' => $city->id,
                    'geo_id' => $geographicalInformation->id,
                ];
                $meterParameter = MeterParameterFactory::new()->create([
                    'owner_type' => 'person',
                    'owner_id' => $person->id,
                    'meter_id' => $meter->id,
                    'tariff_id' => $this->meterTariff->id,
                    'connection_type_id' => $this->connectionType->id,
                    'connection_group_id' => $this->connectionGroup->id,
                ]);
                $meterAddress = Address::query()->make([
                    'city_id' => $meterAddressData['city_id'],
                    'geo_id' => $meterAddressData['geo_id'],
                ]);
                $meterAddress->owner()->associate($meterParameter)->save();
                $geographicalInformation->owner()->associate($meterParameter)->save();

                $personAddressData = [
                    'email' => $this->faker->unique()->email,
                    'phone' => $this->faker->unique()->phoneNumber,
                    'street' => $this->faker->streetAddress,
                    'city_id' => $city->id,
                    'is_primary' => 1,
                ];
                $personAddress = Address::query()->make([
                    'email' => $personAddressData['email'],
                    'phone' => $personAddressData['phone'],
                    'street' => $personAddressData['street'],
                    'city_id' => $personAddressData['city_id'],
                    'is_primary' => $personAddressData['is_primary'],
                ]);
                $personAddress->owner()->associate($person);
                $personAddress->save();
                --$meterCount;

                while ($transactionCount > 0) {
                    $vodacomTransaction =
                        VodacomTransactionFactory::new()->create([
                            'id' => $this->generateUniqueNumber(),
                            'conversation_id' => $this->generateUniqueNumber(),
                            'status' => 1,
                            'originator_conversation_id' => $this->generateUniqueNumber(),
                        ]);
                    $transaction = TransactionFactory::new()->create([
                        'id' => $this->generateUniqueNumber(),
                        'amount' => $this->faker->unique()->randomNumber(4),
                        'sender' => $personAddress->phone,
                        'type' => 'energy',
                        'message' => $meter->serial_number,
                        'original_transaction_id' => $vodacomTransaction->id,
                        'original_transaction_type' => 'vodacom_transaction',
                        'created_at' => Carbon::now()->subDays(2),
                    ]);
                    $token = MeterTokenFactory::new()->create([
                        'transaction_id' => $transaction->id,
                        'energy' => $this->faker->randomFloat(),
                        'meter_id' => $meter->id,
                        'token' => $this->faker->unique()->randomNumber(),
                    ]);
                    $paymentHistory = PaymentHistoryFactory::new()->create([
                        'id' => $this->generateUniqueNumber(),
                        'transaction_id' => $transaction->id,
                        'amount' => $transaction->amount,
                        'payment_service' => 'vodacom_transaction',
                        'sender' => $personAddress->phone,
                        'payment_type' => 'energy',
                        'paid_for_type' => 'token',
                        'paid_for_id' => $token->id,
                        'payer_type' => 'person',
                        'payer_id' => $person->id,
                    ]);
                    --$transactionCount;
                }
            }
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
        return $this->faker->unique()->randomNumber() + $this->faker->unique()->randomNumber() +
            $this->faker->unique()->randomNumber();
    }
}

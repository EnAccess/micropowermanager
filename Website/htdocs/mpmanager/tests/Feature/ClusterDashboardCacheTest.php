<?php

namespace Tests\Feature;

use App\Models\Address\Address;
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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Cache;

class ClusterDashboardCacheTest extends TestCase
{
    use RefreshMultipleDatabases, WithFaker;

    private $user, $company, $city, $connectionType, $manufacturer, $meterType, $meter, $meterParameter,
        $meterTariff, $person, $token, $transaction, $clusterIds = [];

    public function test_user_updates_cluster_data_in_cache()
    {
        $clusterCount = 1;
        $meterCount = 2;
        $transactionCount = 3;
        $this->createTestData($clusterCount, $meterCount, $transactionCount);
        $response = $this->actingAs($this->user)->put('/api/dashboard/clusters');
        $response->assertStatus(200);
        $this->assertEquals(count(Cache::get('ClustersList')), count($this->clusterIds));

    }

    public function test_user_gets_cluster_data_from_cache_by_id()
    {
        $clusterCount = 2;
        $meterCount = 2;
        $transactionCount = 3;
        $this->createTestData($clusterCount, $meterCount, $transactionCount);
        $this->actingAs($this->user)->put('/api/dashboard/clusters');
        $response = $this->actingAs($this->user)->get(sprintf('/api/dashboard/clusters/%s', $this->clusterIds[0]));
        $this->assertEquals($response['data']['id'], $this->clusterIds[0]);
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
            $clusterCount--;


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
                $meterCount--;


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
                    $transactionCount--;
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
        return ($this->faker->unique()->randomNumber() + $this->faker->unique()->randomNumber() +
            $this->faker->unique()->randomNumber());
    }
}

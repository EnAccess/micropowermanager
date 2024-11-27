<?php

namespace Tests\Feature;

use App\Models\AccessRate\AccessRate;
use App\Models\Meter\MeterTariff;
use App\Models\SocialTariff;
use App\Models\TimeOfUsage;
use App\Services\TariffPricingComponentService;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class MeterTariffTest extends TestCase {
    use CreateEnvironments;

    public function testUserGetsMeterTariffList() {
        $this->createTestData();
        $meterTariffCount = 5;
        $this->createMeterTariff($meterTariffCount);
        $response = $this->actingAs($this->user)->get('/api/tariffs');
        $response->assertStatus(200);
        $this->assertEquals(count($response['data']), count($this->meterTariffs));
    }

    public function testUserGetsMeterTariffById() {
        $this->createTestData();
        $this->createMeterTariff();
        $response = $this->actingAs($this->user)->get(sprintf('/api/tariffs/%s', $this->meterTariffs[0]->id));
        $response->assertStatus(200);
        $this->assertEquals($response['data']['id'], $this->meterTariffs[0]->id);
    }

    public function testUserUpdatesAMeterTariff() {
        Queue::fake();
        $this->createTestData();
        $this->createMeterTariff();
        $tariffData = [
            'name' => 'Updated Tariff',
            'price' => 20,
            'currency' => '$',
            'factor' => 1,
            'components' => [
                [
                    'name' => 'Cost-1',
                    'price' => 10000,
                ],
            ],
            'social_tariff' => [
                'daily_allowance' => 10,
                'price' => 100000,
                'initial_energy_budget' => 4,
                'maximum_stacked_energy' => 34,
            ],
            'time_of_usage' => [
                [
                    'start' => '05:00',
                    'end' => '07:00',
                    'value' => 20,
                    'cost' => 2,
                ],
            ],
            'access_rate' => [
                'access_rate_period' => 30,
                'access_rate_amount' => 100,
            ],
        ];
        $response = $this->actingAs($this->user)->put(sprintf(
            '/api/tariffs/%s',
            $this->meterTariffs[0]->id
        ), $tariffData);
        $response->assertStatus(200);
        $this->assertEquals($response['data']['name'], $tariffData['name']);
        $this->assertEquals($this->meterTariffs[0]->id, SocialTariff::query()->first()->tariff_id);
        $this->assertEquals($this->meterTariffs[0]->id, TimeOfUsage::query()->first()->tariff_id);
        $this->assertEquals($this->meterTariffs[0]->id, AccessRate::query()->first()->tariff_id);
    }

    public function testUserCreatesBasicTariff(): void {
        $this->createTestData();
        $tariffData = [
            'name' => 'Tariff',
            'price' => 10000,
            'currency' => 'TRY',
            'factor' => 1,
        ];
        $response = $this->actingAs($this->user)->post('/api/tariffs', $tariffData);
        $response->assertStatus(201);
        $this->assertCount(1, MeterTariff::all());
        $this->assertEquals(10000, MeterTariff::first()->total_price);
    }

    public function testUserCreatesTariffWithPriceComponents(): void {
        Queue::fake();
        $this->createTestData();
        $tariffData = [
            'name' => 'Tariff',
            'price' => 10000,
            'currency' => 'TRY',
            'factor' => 1,
            'components' => [
                [
                    'name' => 'Cost-1',
                    'price' => 10000,
                ],
            ],
        ];
        $response = $this->actingAs($this->user)->post('/api/tariffs', $tariffData);
        $response->assertStatus(201);
        $this->assertCount(1, MeterTariff::all());
    }

    public function testUserCreatesTariffWithSocialInputs(): void {
        $this->createTestData();
        $tariffData = [
            'name' => 'Tariff',
            'price' => 10000,
            'currency' => 'TRY',
            'factor' => 1,
            'social_tariff' => [
                'daily_allowance' => 10,
                'price' => 100000,
                'initial_energy_budget' => 4,
                'maximum_stacked_energy' => 34,
            ],
        ];
        $response = $this->actingAs($this->user)->post('/api/tariffs', $tariffData);
        $response->assertStatus(201);
        $this->assertCount(1, MeterTariff::all());
        $this->assertEquals(MeterTariff::query()->first()->id, SocialTariff::query()->first()->tariff_id);
    }

    public function testUserCreatesTariffWithTimeOfUsages(): void {
        $this->createTestData();
        $tariffData = [
            'name' => 'Test Tariff',
            'price' => 10,
            'currency' => '$',
            'factor' => 1,
            'time_of_usage' => [
                [
                    'start' => '02:00',
                    'end' => '04:00',
                    'value' => 10,
                    'cost' => 1,
                ],
                [
                    'start' => '05:00',
                    'end' => '07:00',
                    'value' => 20,
                    'cost' => 2,
                ],
            ],
        ];
        $response = $this->actingAs($this->user)->post('/api/tariffs', $tariffData);
        $response->assertStatus(201);
        $this->assertCount(1, MeterTariff::all());
        $this->assertEquals(MeterTariff::query()->first()->id, TimeOfUsage::query()->first()->tariff_id);
    }

    public function testUserCreatesTariffWithAccessRate(): void {
        $this->createTestData();
        $tariffData = [
            'name' => 'Test Tariff',
            'price' => 10,
            'currency' => '$',
            'factor' => 1,
            'access_rate' => [
                'access_rate_period' => 30,
                'access_rate_amount' => 100,
            ],
        ];
        $response = $this->actingAs($this->user)->post('/api/tariffs', $tariffData);
        $response->assertStatus(201);
        $this->assertCount(1, MeterTariff::all());
        $this->assertEquals(MeterTariff::query()->first()->id, AccessRate::query()->first()->tariff_id);
    }

    public function testUserGetsMeterListForATariff(): void {
        $this->createTestData();
        $this->createMeterManufacturer();
        $this->createMeterType();
        $this->createMeterTariff();
        $this->createConnectionGroup();
        $this->createConnectionType();
        $meterCount = 5;
        $this->createMeter($meterCount);
        $response = $this->actingAs($this->user)->get(sprintf(
            '/api/tariffs/%s/usage-count',
            $this->meterTariffs[0]->id
        ));

        $response->assertStatus(200);

        $this->assertEquals($response['data']['count'], $meterCount);
    }

    public function testComponentPriceChangesTotalPrice(): void {
        $this->createTestData();
        $this->createMeterTariff();
        $tariff = $this->meterTariff;
        $tariffPrice = $tariff->total_price;
        $tariffPricingComponentService = app()->make(TariffPricingComponentService::class);
        $componentsData = [
            [
                'name' => 'Some component',
                'price' => 100000,
            ], [
                'name' => 'Some other component',
                'price' => 100000,
            ]];
        $updatedTariff = $tariff->fresh();
        $this->assertEquals($tariffPrice + 200000, $updatedTariff->total_price);
    }

    public function actingAs($user, $driver = null) {
        $token = JWTAuth::fromUser($user);
        $this->withHeader('Authorization', "Bearer {$token}");
        parent::actingAs($user);

        return $this;
    }
}

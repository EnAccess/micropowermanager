<?php

namespace Tests\Feature;

use App\Models\SubConnectionType;
use Tests\CreateEnvironments;
use Tests\TestCase;

class SubConnectionTypeTest extends TestCase {
    use CreateEnvironments;

    public function testUserGetsSubConnectionTypeList(): void {
        $connectionTypeCount = 1;
        $subConnectionTypeCount = 1;
        $meterTariffCount = 1;
        $this->createTestData();
        $this->createMeterTariff($meterTariffCount);
        $this->createConnectionType($connectionTypeCount, $subConnectionTypeCount);
        $expectedCount = SubConnectionType::query()->count();
        $response = $this->actingAs($this->user)->get('/api/sub-connection-types');
        $response->assertStatus(200);
        $this->assertCount($expectedCount, $response['data']);
    }

    public function testUserGetsSubConnectionTypesByConnectionTypeId(): void {
        $connectionTypeCount = 2;
        $subConnectionTypeCount = 1;
        $meterTariffCount = 1;
        $this->createTestData();
        $this->createMeterTariff($meterTariffCount);
        $this->createConnectionType($connectionTypeCount, $subConnectionTypeCount);
        $response = $this->actingAs($this->user)->get(sprintf(
            '/api/sub-connection-types/%s',
            $this->connectonTypes[0]->id
        ));
        $response->assertStatus(200);
        $this->assertEquals($response['data'][0]['connection_type_id'], $this->connectonTypes[0]->id);
    }

    public function testUserCreatesNewSubConnectionType(): void {
        $connectionTypeCount = 1;
        $subConnectionTypeCount = 0;
        $meterTariffCount = 1;
        $this->createTestData();
        $this->createMeterTariff($meterTariffCount);
        $this->createConnectionType($connectionTypeCount, $subConnectionTypeCount);
        $subConnectionTypeData = [
            'name' => 'Test SubConnection Type',
            'connection_type_id' => $this->connectonTypes[0]->id,
            'tariff_id' => $this->meterTariff->id,
        ];
        $response = $this->actingAs($this->user)->post('/api/sub-connection-types', $subConnectionTypeData);
        $response->assertStatus(201);
        $this->assertEquals($response['data']['name'], $subConnectionTypeData['name']);
    }

    public function testUserUpdatesASubConnectionType(): void {
        $connectionTypeCount = 1;
        $subConnectionTypeCount = 1;
        $meterTariffCount = 1;
        $this->createTestData();
        $this->createMeterTariff($meterTariffCount);
        $this->createConnectionType($connectionTypeCount, $subConnectionTypeCount);
        $subConnectionTypeData = ['name' => 'Updated SubConnection Type'];
        $response = $this->actingAs($this->user)->put(sprintf(
            '/api/sub-connection-types/%s',
            $this->subConnectionTypes[0]->id
        ), $subConnectionTypeData);
        $response->assertStatus(200);
        $this->assertEquals($response['data']['name'], $subConnectionTypeData['name']);
    }
}

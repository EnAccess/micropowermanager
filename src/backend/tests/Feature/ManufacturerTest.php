<?php

namespace Tests\Feature;

use App\Models\Manufacturer;
use App\Plugins\KelinMeter\KelinMeterApi;
use App\Services\ManufacturerService;
use Tests\CreateEnvironments;
use Tests\TestCase;

class ManufacturerTest extends TestCase {
    use CreateEnvironments;

    public function testUserGetsManufacturerList(): void {
        $this->createTestData();
        $manufacturerCount = 4;
        $this->createMeterManufacturer($manufacturerCount);
        $response = $this->actingAs($this->user)->get('/api/manufacturers');
        $response->assertStatus(200);
        $this->assertEquals(count($response['data']), count($this->manufacturers));
    }

    public function testUserGetsManufacturerById(): void {
        $this->createTestData();
        $this->createMeterManufacturer();
        $response = $this->actingAs($this->user)->get(sprintf('/api/manufacturers/%s', $this->manufacturers[0]->id));
        $response->assertStatus(200);
        $this->assertEquals($response['data']['id'], $this->manufacturers[0]->id);
    }

    public function testUserCreatesNewManufacturer(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $manufacturerData = [
            'name' => 'test meters company',
            'website' => $this->faker->url(),
            'api_name' => 'CalinMeterApi',
            'email' => 'test@test.com',
            'city_id' => $this->cities[0]->id,
            'phone' => $this->faker->e164PhoneNumber(),
        ];
        $response = $this->actingAs($this->user)->post('/api/manufacturers', $manufacturerData);
        $response->assertStatus(201);
        $this->assertEquals($response['data']['name'], $manufacturerData['name']);
    }

    public function testCreatingAManufacturerRejectsAnApiNameThatIsNotAManufacturerApi(): void {
        $this->createTestData();

        foreach (['NotARegisteredApi', 'db'] as $apiName) {
            $this->actingAs($this->user)
                ->postJson('/api/manufacturers', ['name' => 'Unknown API company', 'api_name' => $apiName])
                ->assertStatus(422)
                ->assertJsonPath('errors.api_name.0', 'The api name is not a registered manufacturer API.');
        }
        $this->assertFalse(Manufacturer::query()->where('name', 'Unknown API company')->exists());
    }

    public function testKelinMetersResolveToTheirManufacturerApi(): void {
        $this->assertInstanceOf(KelinMeterApi::class, resolve(ManufacturerService::class)->resolveApi('KelinMeterApi'));
    }
}

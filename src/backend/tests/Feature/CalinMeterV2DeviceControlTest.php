<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Manufacturer;
use App\Models\Meter\Meter;
use App\Models\MpmPlugin;
use App\Models\Token;
use App\Plugins\CalinMeterV2\Services\CalinV2CredentialService;
use App\Plugins\CalinMeterV2\Services\ManufacturerService;
use Database\Factories\DeviceFactory;
use Database\Factories\Meter\MeterFactory;
use Database\Factories\Meter\MeterTypeFactory;
use Database\Factories\Person\PersonFactory;
use Database\Factories\TariffFactory;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Tests\CreateEnvironments;
use Tests\TestCase;

class CalinMeterV2DeviceControlTest extends TestCase {
    use CreateEnvironments;

    public function testItIssuesAnAdHocTopUpAndAClearCreditTokenThroughCalin(): void {
        $this->createTestData();
        $this->activatePlugin(MpmPlugin::CALIN_METER_V2);
        $this->fakeCalin(
            $this->envelope(['token' => 'login-token']),
            $this->envelope(['token' => '11112222333344445555', 'totalUnit' => 3.2]),
            $this->envelope(['token' => '99998888777766665555']),
        );
        $this->app->make(CalinV2CredentialService::class)->updateCredentials([
            'api_url' => 'http://calin.example.com:8080',
            'company' => null,
            'user_id' => 'user-1',
            'password' => 'secret',
        ]);
        $device = $this->seedCalinMeter();

        $this->assertSame(MpmPlugin::CALIN_METER_V2, $device->vending_plugin_id);
        $this->actingAs($this->user)
            ->getJson("/api/devices/{$device->id}/capabilities")
            ->assertJsonPath('data.token_types', ['add_credit', 'clear_credit']);

        $this->actingAs($this->user)
            ->postJson("/api/devices/{$device->id}/token", ['amount' => 6000, 'unit' => 'currency'])
            ->assertStatus(201)
            ->assertJsonPath('data.token', '11112222333344445555')
            ->assertJsonPath('data.token_amount', 3.2);

        $this->actingAs($this->user)
            ->postJson("/api/devices/{$device->id}/token", ['type' => 'clear_credit'])
            ->assertStatus(201)
            ->assertJsonPath('data.token', '99998888777766665555')
            ->assertJsonPath('data.token_type', Token::TYPE_CLEAR_CREDIT);

        $this->assertSame(2, Token::query()->where('device_id', $device->id)->count());
    }

    private function seedCalinMeter(): Device {
        $this->app->make(ManufacturerService::class)->register();
        $meter = MeterFactory::new()->create([
            'manufacturer_id' => Manufacturer::query()->where('api_name', 'CalinMeterV2Api')->firstOrFail()->id,
            'meter_type_id' => MeterTypeFactory::new()->create()->id,
            'tariff_id' => TariffFactory::new()->create(['price' => 1830])->id,
        ]);

        return DeviceFactory::new()->create([
            'person_id' => PersonFactory::new()->create()->id,
            'device_id' => $meter->id,
            'device_type' => Meter::RELATION_NAME,
        ])->refresh();
    }

    private function fakeCalin(Response ...$responses): void {
        $this->app->instance(Client::class, new Client(['handler' => HandlerStack::create(new MockHandler($responses))]));
    }

    /**
     * @param array<string, mixed> $result
     */
    private function envelope(array $result): Response {
        return new Response(200, [], (string) json_encode(['code' => 0, 'reason' => 'success', 'result' => $result]));
    }
}

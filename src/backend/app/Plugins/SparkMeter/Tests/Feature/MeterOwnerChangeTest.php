<?php

namespace App\Plugins\SparkMeter\Tests\Feature;

use App\Models\City;
use App\Models\Device;
use App\Models\Meter\Meter;
use App\Models\MiniGrid;
use App\Models\Person\Person;
use App\Models\Tariff;
use App\Models\User;
use App\Plugins\SparkMeter\Models\SmCustomer;
use App\Plugins\SparkMeter\Models\SmSite;
use App\Plugins\SparkMeter\Models\SmTariff;
use App\Plugins\SparkMeter\Services\CustomerService;
use Database\Factories\ClusterFactory;
use Database\Factories\ManufacturerFactory;
use Database\Factories\Meter\MeterFactory;
use Database\Factories\Person\PersonFactory;
use Database\Factories\TariffFactory;
use Database\Factories\UserFactory;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class MeterOwnerChangeTest extends TestCase {
    private const string SITE_ID = 'site-1';

    private User $user;
    private Tariff $tariff;

    /** @var array<int, array<string, mixed>> */
    private array $history = [];

    protected function setUp(): void {
        parent::setUp();
        $this->user = UserFactory::new()->create();
        $this->tariff = TariffFactory::new()->create();
        $cluster = ClusterFactory::new()->create(['manager_id' => $this->user->id]);
        $miniGrid = MiniGrid::query()->create(['name' => 'Ground', 'cluster_id' => $cluster->id]);
        City::query()->create(['name' => 'Ground Village', 'mini_grid_id' => $miniGrid->id, 'country_id' => 0]);
        SmSite::query()->create([
            'site_id' => self::SITE_ID,
            'mpm_mini_grid_id' => $miniGrid->id,
            'thundercloud_url' => 'https://ground.test/api/v0',
            'thundercloud_token' => 'token',
            'is_authenticated' => 1,
            'is_online' => 1,
        ]);
        $handler = HandlerStack::create(new MockHandler([
            new Response(200, [], (string) json_encode(['customer_id' => 'customer-1', 'error' => null])),
        ]));
        $handler->push(Middleware::history($this->history));
        $this->app->instance(Client::class, new Client(['handler' => $handler]));
    }

    private function createMeterDevice(Person $owner, string $apiName, string $serial = 'SM5R-01-0000ABCD'): Device {
        $manufacturer = ManufacturerFactory::new()->isMeterManufacturer()->create(['api_name' => $apiName]);
        $meter = MeterFactory::new()->create([
            'manufacturer_id' => $manufacturer->id,
            'tariff_id' => $this->tariff->id,
            'serial_number' => $serial,
        ]);

        return Device::query()->create([
            'person_id' => $owner->id,
            'device_type' => Meter::RELATION_NAME,
            'device_id' => $meter->id,
            'device_serial' => $meter->serial_number,
        ]);
    }

    private function reassign(Device $device, Person $newOwner): void {
        $this->actingAs($this->user)->putJson("/api/devices/{$device->id}", [
            'id' => $device->id,
            'person_id' => $newOwner->id,
            'device_type' => $device->device_type,
            'device_serial' => $device->device_serial,
            'device_id' => $device->device_id,
        ])->assertOk();
    }

    public function testReassigningASparkMeterMeterMovesTheCustomerToTheNewOwner(): void {
        $previousOwner = PersonFactory::new()->create();
        $newOwner = PersonFactory::new()->create(['name' => 'New', 'surname' => 'Owner']);
        $device = $this->createMeterDevice($previousOwner, 'SparkMeterApi');
        $smCustomer = SmCustomer::query()->create([
            'customer_id' => 'customer-1',
            'site_id' => self::SITE_ID,
            'mpm_customer_id' => $previousOwner->id,
            'credit_balance' => 0,
            'low_balance_limit' => 0,
            'hash' => 'stale',
        ]);

        $this->reassign($device, $newOwner);

        $smCustomer->refresh();
        $this->assertSame($newOwner->id, $smCustomer->mpm_customer_id);
        $this->assertNotSame('stale', $smCustomer->hash);
        $this->assertCount(1, $this->history);
        $request = $this->history[0]['request'];
        $this->assertSame('PUT', $request->getMethod());
        $this->assertSame('/api/v0/customers/customer-1', $request->getUri()->getPath());
        $this->assertSame('New Owner', json_decode((string) $request->getBody(), true)['name']);
    }

    public function testReassigningAnotherManufacturersMeterLeavesSparkMeterAlone(): void {
        $previousOwner = PersonFactory::new()->create();
        $newOwner = PersonFactory::new()->create();
        $device = $this->createMeterDevice($previousOwner, 'CalinMeterApi');
        $smCustomer = SmCustomer::query()->create([
            'customer_id' => 'customer-1',
            'site_id' => self::SITE_ID,
            'mpm_customer_id' => $previousOwner->id,
            'credit_balance' => 0,
            'low_balance_limit' => 0,
            'hash' => 'stale',
        ]);

        $this->reassign($device, $newOwner);

        $this->assertSame($previousOwner->id, $smCustomer->refresh()->mpm_customer_id);
        $this->assertCount(0, $this->history);
    }

    public function testSyncUpdatesTheMeterMatchingTheCustomerSerial(): void {
        $owner = PersonFactory::new()->create();
        $otherDevice = $this->createMeterDevice($owner, 'SparkMeterApi', 'SM5R-01-00000001');
        $sparkDevice = $this->createMeterDevice($owner, 'SparkMeterApi', 'SM5R-01-00000002');
        $syncedTariff = TariffFactory::new()->create(['name' => 'ET2']);
        SmTariff::query()->create([
            'site_id' => self::SITE_ID,
            'tariff_id' => 'tariff-2',
            'mpm_tariff_id' => $syncedTariff->id,
            'flat_load_limit' => 30,
            'hash' => 'irrelevant',
        ]);

        resolve(CustomerService::class)->updateRelatedPerson(
            $this->sparkCustomer('SM5R-01-00000002', 'ET2'),
            $owner,
            self::SITE_ID
        );

        $this->assertSame($syncedTariff->id, Meter::query()->findOrFail($sparkDevice->device_id)->tariff_id);
        $this->assertSame($this->tariff->id, Meter::query()->findOrFail($otherDevice->device_id)->tariff_id);
    }

    public function testSyncToleratesAPersonWhoNoLongerOwnsTheMeter(): void {
        $previousOwner = PersonFactory::new()->create();

        resolve(CustomerService::class)->updateRelatedPerson(
            $this->sparkCustomer('SM5R-01-0000ABCD', 'ET1'),
            $previousOwner,
            self::SITE_ID
        );

        $this->assertSame('Synced Name', $previousOwner->refresh()->name);
    }

    /**
     * @return array<string, mixed>
     */
    private function sparkCustomer(string $serial, string $tariffName): array {
        return [
            'name' => 'Synced Name',
            'phone_number' => '+2348000000001',
            'meters' => [[
                'serial' => $serial,
                'current_tariff_name' => $tariffName,
                'street1' => 'Main Street',
            ]],
        ];
    }
}

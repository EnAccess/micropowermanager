<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Manufacturer;
use App\Models\Meter\Meter;
use App\Plugins\BulkRegistration\Services\ManufacturerService as BulkRegistrationManufacturerService;
use App\Services\ManufacturerService;
use Database\Factories\Meter\MeterFactory;
use Database\Factories\UserFactory;
use Tests\TestCase;

class ManufacturerPreviousNamesTest extends TestCase {
    public function testRenamingAManufacturerRecordsEachPreviousName(): void {
        $manufacturer = Manufacturer::query()->create(['name' => 'First Name', 'type' => 'meter', 'api_name' => 'TestApi']);

        $manufacturer->update(['name' => 'Second Name']);
        $manufacturer->update(['name' => 'Third Name']);

        $this->assertSame(['First Name', 'Second Name'], $manufacturer->fresh()?->previous_names);
    }

    public function testRenamingBackToAPreviousNameDropsItFromPreviousNames(): void {
        $manufacturer = Manufacturer::query()->create(['name' => 'First Name', 'type' => 'meter', 'api_name' => 'TestApi']);

        $manufacturer->update(['name' => 'Second Name']);
        $manufacturer->update(['name' => 'First Name']);

        $this->assertSame(['Second Name'], $manufacturer->fresh()?->previous_names);
    }

    public function testUpdatingOtherFieldsLeavesPreviousNamesUntouched(): void {
        $manufacturer = Manufacturer::query()->create(['name' => 'First Name', 'type' => 'meter', 'api_name' => 'TestApi']);

        $manufacturer->update(['website' => 'https://example.com']);

        $this->assertNull($manufacturer->fresh()?->previous_names);
    }

    public function testGetByNameResolvesAPreviousName(): void {
        $manufacturer = $this->renamedCalinManufacturer();

        $resolved = $this->app->make(ManufacturerService::class)->getByName('Calin Meters');

        $this->assertSame($manufacturer->id, $resolved?->id);
    }

    public function testRenameMigrationKeepsMetersOnTheSameManufacturerRow(): void {
        $manufacturer = Manufacturer::query()->create(['name' => 'Calin Meters', 'type' => 'meter', 'api_name' => 'CalinMeterApi']);
        $meter = MeterFactory::new()->create(['manufacturer_id' => $manufacturer->id]);
        $manufacturerCount = Manufacturer::query()->count();

        $migration = require database_path('migrations/tenant/2026_10_31_000001_rename_calin_meter_manufacturer.php');
        $migration->up();

        $renamed = $manufacturer->refresh();
        $this->assertSame($manufacturerCount, Manufacturer::query()->count());
        $this->assertSame('Calin (Legacy Token API)', $renamed->name);
        $this->assertSame(['Calin Meters'], $renamed->previous_names);
        $this->assertSame($manufacturer->id, Meter::query()->findOrFail($meter->id)->manufacturer_id);
    }

    public function testDeviceImportWithAPreRenameManufacturerNameAttachesToTheRenamedRow(): void {
        $manufacturer = $this->renamedCalinManufacturer();
        $user = UserFactory::new()->create();
        $this->assignPermission($user, 'customers');

        $response = $this->actingAs($user)->postJson('/api/import/devices', [
            'data' => [
                ['device_info' => ['serial_number' => 'CALIN-LEGACY-IMPORT-1', 'type' => 'meter', 'manufacturer' => 'Calin Meters']],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.imported_count', 1);
        $this->assertSame(1, Manufacturer::query()->where('api_name', 'CalinMeterApi')->count());
        $this->assertSame(0, Manufacturer::query()->where('name', 'Calin Meters')->count());
        $this->assertSame($manufacturer->id, Meter::query()->where('serial_number', 'CALIN-LEGACY-IMPORT-1')->firstOrFail()->manufacturer_id);
    }

    public function testBulkRegistrationWithCalinMeterResolvesToTheRenamedRow(): void {
        $manufacturer = $this->renamedCalinManufacturer();

        $resolved = $this->app->make(BulkRegistrationManufacturerService::class)
            ->resolveCsvDataFromComingRow(['Meter Manufacturer Name' => 'Calin Meter']);

        $this->assertInstanceOf(Manufacturer::class, $resolved);
        $this->assertSame($manufacturer->id, $resolved->id);
        $this->assertSame('CalinMeterApi', $resolved->api_name);
        $this->assertSame(1, Manufacturer::query()->where('api_name', 'CalinMeterApi')->count());
    }

    private function renamedCalinManufacturer(): Manufacturer {
        $manufacturer = Manufacturer::query()->create(['name' => 'Calin Meters', 'type' => 'meter', 'api_name' => 'CalinMeterApi']);
        $manufacturer->update(['name' => 'Calin (Legacy Token API)']);

        return $manufacturer;
    }
}

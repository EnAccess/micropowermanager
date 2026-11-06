<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Manufacturer;
use App\Models\Meter\Meter;
use App\Plugins\BulkRegistration\Services\ManufacturerService as BulkRegistrationManufacturerService;
use Database\Factories\Meter\MeterFactory;
use Tests\TestCase;

class CalinMeterManufacturerRenameTest extends TestCase {
    public function testRenameMigrationKeepsMetersOnTheSameManufacturerRow(): void {
        $manufacturer = Manufacturer::query()->create(['name' => 'Calin Meters', 'type' => 'meter', 'api_name' => 'CalinMeterApi']);
        $meter = MeterFactory::new()->create(['manufacturer_id' => $manufacturer->id]);
        $manufacturerCount = Manufacturer::query()->count();

        $migration = require database_path('migrations/tenant/2026_10_31_000000_rename_calin_meter_manufacturer.php');
        $migration->up();

        $this->assertSame($manufacturerCount, Manufacturer::query()->count());
        $this->assertSame('Calin Meter (Legacy Token API)', $manufacturer->refresh()->name);
        $this->assertSame($manufacturer->id, Meter::query()->findOrFail($meter->id)->manufacturer_id);
    }

    public function testBulkRegistrationWithCalinMeterResolvesToTheRenamedRow(): void {
        $manufacturer = Manufacturer::query()->create(['name' => 'Calin Meter (Legacy Token API)', 'type' => 'meter', 'api_name' => 'CalinMeterApi']);

        $resolved = $this->app->make(BulkRegistrationManufacturerService::class)
            ->resolveCsvDataFromComingRow(['Meter Manufacturer Name' => 'Calin Meter']);

        $this->assertInstanceOf(Manufacturer::class, $resolved);
        $this->assertSame($manufacturer->id, $resolved->id);
        $this->assertSame(1, Manufacturer::query()->where('api_name', 'CalinMeterApi')->count());
    }
}

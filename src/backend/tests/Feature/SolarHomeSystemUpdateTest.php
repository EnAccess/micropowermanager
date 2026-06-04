<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Appliance;
use App\Models\Manufacturer;
use App\Models\SolarHomeSystem;
use Database\Factories\ApplianceFactory;
use Database\Factories\ApplianceTypeFactory;
use Database\Factories\ManufacturerFactory;
use Database\Factories\SolarHomeSystemFactory;
use Tests\CreateEnvironments;
use Tests\TestCase;

class SolarHomeSystemUpdateTest extends TestCase {
    use CreateEnvironments;

    public function testItUpdatesManufacturerAndAppliance(): void {
        $this->createTestData();
        [$solarHomeSystem, $newManufacturer, $newAppliance] = $this->seedSolarHomeSystem();

        $response = $this->actingAs($this->user)->put(
            "/api/solar-home-systems/{$solarHomeSystem->id}",
            ['manufacturer_id' => $newManufacturer->id, 'appliance_id' => $newAppliance->id]
        );

        $response->assertStatus(200);
        $solarHomeSystem->refresh();
        $this->assertSame($newManufacturer->id, $solarHomeSystem->manufacturer_id);
        $this->assertSame($newAppliance->id, $solarHomeSystem->appliance_id);
    }

    public function testItValidatesManufacturerAndApplianceExist(): void {
        $this->createTestData();
        [$solarHomeSystem] = $this->seedSolarHomeSystem();

        $response = $this->actingAs($this->user)->putJson(
            "/api/solar-home-systems/{$solarHomeSystem->id}",
            ['manufacturer_id' => 999999, 'appliance_id' => 999999]
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['manufacturer_id', 'appliance_id']);
    }

    public function testItReturns404WhenSolarHomeSystemMissing(): void {
        $this->createTestData();
        $manufacturer = ManufacturerFactory::new()->isShsManufacturer()->create();
        $appliance = $this->createAppliance();

        $response = $this->actingAs($this->user)->putJson(
            '/api/solar-home-systems/999999',
            ['manufacturer_id' => $manufacturer->id, 'appliance_id' => $appliance->id]
        );

        $response->assertStatus(404);
    }

    /**
     * @return array{0: SolarHomeSystem, 1: Manufacturer, 2: Appliance}
     */
    private function seedSolarHomeSystem(): array {
        $originalManufacturer = ManufacturerFactory::new()->isShsManufacturer()->create();
        $solarHomeSystem = SolarHomeSystemFactory::new()->create([
            'manufacturer_id' => $originalManufacturer->id,
            'appliance_id' => $this->createAppliance()->id,
        ]);

        $newManufacturer = ManufacturerFactory::new()->isShsManufacturer()->create();
        $newAppliance = $this->createAppliance();

        return [$solarHomeSystem, $newManufacturer, $newAppliance];
    }

    private function createAppliance(): Appliance {
        return ApplianceFactory::new()->create([
            'appliance_type_id' => ApplianceTypeFactory::new()->create()->id,
        ]);
    }
}

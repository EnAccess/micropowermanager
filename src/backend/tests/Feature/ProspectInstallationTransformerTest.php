<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Device;
use App\Models\SolarHomeSystem;
use App\Plugins\Prospect\Services\ProspectInstallationTransformer;
use Database\Factories\ApplianceFactory;
use Database\Factories\ApplianceTypeFactory;
use Database\Factories\DeviceFactory;
use Database\Factories\ManufacturerFactory;
use Database\Factories\Person\PersonFactory;
use Database\Factories\SolarHomeSystemFactory;
use Illuminate\Support\Facades\Queue;
use Tests\CreateEnvironments;
use Tests\TestCase;

class ProspectInstallationTransformerTest extends TestCase {
    use CreateEnvironments;

    public function testAWeeklyPlanReportsItsPeriodAndTheDaysItsDownPaymentCovers(): void {
        $this->createTestData();
        Queue::fake();
        $device = $this->seedSolarHomeSystem();
        $customer = PersonFactory::new()->create();

        // 680 at 160 a week buys 29.75 days, vended as 30.
        $this->actingAs($this->user)->post(
            "/api/appliances/person/{$device->device->appliance_id}/people/{$customer->id}",
            [
                'user_id' => $this->user->id,
                'device_serial' => $device->device_serial,
                'points' => '0,0',
                'cost' => 2600,
                'rate' => 12,
                'rate_type' => 'weekly',
                'down_payment' => 680,
            ]
        )->assertStatus(200);

        $installation = resolve(ProspectInstallationTransformer::class)->transform($device->fresh());

        $this->assertSame(7, $installation['payment_plan_installment_period_days']);
        $this->assertSame(84, $installation['payment_plan_days_financed']);
        $this->assertSame(30, $installation['payment_plan_days_down_payment']);
    }

    private function seedSolarHomeSystem(): Device {
        $manufacturer = ManufacturerFactory::new()->create(['type' => 'shs']);
        $appliance = ApplianceFactory::new()->create([
            'price' => 2600,
            'appliance_type_id' => ApplianceTypeFactory::new()->create()->id,
        ]);
        $solarHomeSystem = SolarHomeSystemFactory::new()->create([
            'manufacturer_id' => $manufacturer->id,
            'appliance_id' => $appliance->id,
        ]);

        return DeviceFactory::new()->create([
            'person_id' => null,
            'device_id' => $solarHomeSystem->id,
            'device_type' => SolarHomeSystem::RELATION_NAME,
        ]);
    }
}

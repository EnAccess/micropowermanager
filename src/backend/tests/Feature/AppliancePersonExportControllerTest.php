<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\ExportServices\AbstractExportService;
use Database\Factories\ApplianceFactory;
use Database\Factories\AppliancePersonFactory;
use Database\Factories\ApplianceTypeFactory;
use Database\Factories\Person\PersonFactory;
use Database\Factories\UserFactory;
use Tests\TestCase;

class AppliancePersonExportControllerTest extends TestCase {
    public function testExportReturnsAppliancePeopleAsJson(): void {
        $user = UserFactory::new()->create();
        $this->assignPermission($user, 'exports');

        $person = PersonFactory::new()->create(['name' => 'Ada', 'surname' => 'Lovelace']);
        $applianceType = ApplianceTypeFactory::new()->create(['name' => 'Solar Home System']);
        $appliance = ApplianceFactory::new()->create(['name' => 'Solar Kit', 'appliance_type_id' => $applianceType->id]);
        AppliancePersonFactory::new()->create([
            'person_id' => $person->id,
            'appliance_id' => $appliance->id,
            'payment_type' => 'installment',
            'total_cost' => 300,
            'rate_count' => 3,
            'device_serial' => 'SER-123',
        ]);

        $response = $this->actingAs($user)->getJson('/api/export/appliance-people?format=json');

        $response->assertStatus(200);
        $response->assertJsonPath('meta.total', 1);
        $response->assertJsonPath('data.0.customer_name', 'Ada');
        $response->assertJsonPath('data.0.customer_surname', 'Lovelace');
        $response->assertJsonPath('data.0.appliance_name', 'Solar Kit');
        $response->assertJsonPath('data.0.appliance_type', 'Solar Home System');
        $response->assertJsonPath('data.0.payment_type', 'installment');
        $response->assertJsonPath('data.0.device_serial', 'SER-123');
    }

    public function testExportFiltersByPaymentType(): void {
        $user = UserFactory::new()->create();
        $this->assignPermission($user, 'exports');

        $person = PersonFactory::new()->create();
        $appliance = ApplianceFactory::new()->create(['appliance_type_id' => ApplianceTypeFactory::new()->create()->id]);
        AppliancePersonFactory::new()->create([
            'person_id' => $person->id,
            'appliance_id' => $appliance->id,
            'payment_type' => 'installment',
        ]);
        AppliancePersonFactory::new()->create([
            'person_id' => $person->id,
            'appliance_id' => $appliance->id,
            'payment_type' => 'energy_service',
        ]);

        $response = $this->actingAs($user)->getJson('/api/export/appliance-people?format=json&paymentType=energy_service');

        $response->assertStatus(200);
        $response->assertJsonPath('meta.total', 1);
        $response->assertJsonPath('data.0.payment_type', 'energy_service');
    }

    public function testExportReturnsCsvDownload(): void {
        $user = UserFactory::new()->create();
        $this->assignPermission($user, 'exports');

        $person = PersonFactory::new()->create();
        $appliance = ApplianceFactory::new()->create(['appliance_type_id' => ApplianceTypeFactory::new()->create()->id]);
        AppliancePersonFactory::new()->create([
            'person_id' => $person->id,
            'appliance_id' => $appliance->id,
            'payment_type' => 'installment',
        ]);

        $response = $this->actingAs($user)->get('/api/export/appliance-people?format=csv');

        $response->assertStatus(200);
        $this->assertStringContainsString(AbstractExportService::CSV_CONTENT_TYPE, (string) $response->headers->get('content-type'));
    }
}

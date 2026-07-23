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
    public function testExportReturnsAppliancePeopleInEachFormat(): void {
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
        AppliancePersonFactory::new()->create([
            'person_id' => $person->id,
            'appliance_id' => $appliance->id,
            'payment_type' => 'energy_service',
        ]);

        $json = $this->actingAs($user)->getJson('/api/export/appliance-people?format=json');
        $json->assertStatus(200);
        $json->assertJsonPath('meta.total', 2);
        $json->assertJsonPath('data.0.customer_name', 'Ada');
        $json->assertJsonPath('data.0.customer_surname', 'Lovelace');
        $json->assertJsonPath('data.0.appliance_name', 'Solar Kit');
        $json->assertJsonPath('data.0.appliance_type', 'Solar Home System');
        $json->assertJsonPath('data.0.payment_type', 'installment');
        $json->assertJsonPath('data.0.device_serial', 'SER-123');

        $filtered = $this->actingAs($user)->getJson('/api/export/appliance-people?format=json&paymentType=energy_service');
        $filtered->assertStatus(200);
        $filtered->assertJsonPath('meta.total', 1);
        $filtered->assertJsonPath('data.0.payment_type', 'energy_service');

        $csv = $this->actingAs($user)->get('/api/export/appliance-people?format=csv');
        $csv->assertStatus(200);
        $this->assertStringContainsString(AbstractExportService::CSV_CONTENT_TYPE, (string) $csv->headers->get('content-type'));
    }
}

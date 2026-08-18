<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\ExportServices\AbstractExportService;
use Database\Factories\Address\AddressFactory;
use Database\Factories\ApplianceFactory;
use Database\Factories\AppliancePersonFactory;
use Database\Factories\ApplianceTypeFactory;
use Database\Factories\CityFactory;
use Database\Factories\ClusterFactory;
use Database\Factories\DeviceFactory;
use Database\Factories\Meter\MeterFactory;
use Database\Factories\MiniGridFactory;
use Database\Factories\Person\PersonFactory;
use Database\Factories\SolarHomeSystemFactory;
use Database\Factories\UserFactory;
use Tests\TestCase;

class PersonExportControllerTest extends TestCase {
    public function testExportIncludesApplianceClusterAndSiteNameInEachFormat(): void {
        $user = UserFactory::new()->create();
        $this->assignPermission($user, 'exports');

        $cluster = ClusterFactory::new()->create(['name' => 'Northern Cluster', 'manager_id' => $user->id]);
        $miniGrid = MiniGridFactory::new()->create(['name' => 'Abuja Site', 'cluster_id' => $cluster->id]);
        $city = CityFactory::new()->create(['mini_grid_id' => $miniGrid->id]);
        $applianceType = ApplianceTypeFactory::new()->create();
        $directApplianceOnShs = ApplianceFactory::new()->create(['name' => 'Solar Kit', 'appliance_type_id' => $applianceType->id]);
        $financedApplianceOnShs = ApplianceFactory::new()->create(['name' => 'Home Battery', 'appliance_type_id' => $applianceType->id]);
        $financedApplianceOnMeter = ApplianceFactory::new()->create(['name' => 'Prepaid Grid Bundle', 'appliance_type_id' => $applianceType->id]);

        // A customer's site/cluster is derived from their primary address's city
        $person = PersonFactory::new()->isCustomer()->create([
            'name' => 'Ada',
            'surname' => 'Okeke',
        ]);
        $address = AddressFactory::new()->make(['city_id' => $city->id, 'is_primary' => 1]);
        $address->owner()->associate($person);
        $address->save();

        // The SHS device's own appliance_id and its AppliancePerson financing
        // record (matched by device_serial) can genuinely disagree for the same
        // device — both must show up rather than one silently winning.
        $solarHomeSystem = SolarHomeSystemFactory::new()->create(['appliance_id' => $directApplianceOnShs->id]);
        DeviceFactory::new()->create([
            'device_serial' => 'SER-SHS-1',
            'person_id' => $person->id,
            'device_type' => 'solar_home_system',
            'device_id' => $solarHomeSystem->id,
        ]);
        AppliancePersonFactory::new()->create([
            'person_id' => $person->id,
            'appliance_id' => $financedApplianceOnShs->id,
            'device_serial' => 'SER-SHS-1',
        ]);

        // A meter has no appliance_id of its own, but can still carry an
        // AppliancePerson financing record against its serial — a separate,
        // additive appliance rather than a conflict.
        $meter = MeterFactory::new()->create();
        DeviceFactory::new()->create([
            'device_serial' => 'SER-METER-1',
            'person_id' => $person->id,
            'device_type' => 'meter',
            'device_id' => $meter->id,
        ]);
        AppliancePersonFactory::new()->create([
            'person_id' => $person->id,
            'appliance_id' => $financedApplianceOnMeter->id,
            'device_serial' => 'SER-METER-1',
        ]);

        $json = $this->actingAs($user)->getJson('/api/export/customers?format=json');
        $json->assertStatus(200);

        // getAllForExport() has no explicit ordering, and this test seeds a
        // second customer, so locate Ada's row by identity rather than assuming
        // it's data.0.
        /** @var array<int, array<string, mixed>> $data */
        $data = $json->json('data');
        $row = collect($data)->firstWhere('surname', 'Okeke');
        $this->assertNotNull($row);
        $this->assertEqualsCanonicalizing(
            ['Solar Kit', 'Home Battery', 'Prepaid Grid Bundle'],
            explode(', ', (string) $row['appliance_name']),
        );
        $this->assertSame('Northern Cluster', $row['cluster_name']);
        $this->assertSame('Abuja Site', $row['minigrid']);

        $csv = $this->actingAs($user)->get('/api/export/customers?format=csv');
        $csv->assertStatus(200);
        $this->assertStringContainsString(AbstractExportService::CSV_CONTENT_TYPE, (string) $csv->headers->get('content-type'));

        $csvContent = $csv->streamedContent();
        $this->assertStringContainsString('Appliance Name', $csvContent);
        $this->assertStringContainsString('Cluster Name', $csvContent);
        $this->assertStringContainsString('Site Name', $csvContent);
        $this->assertStringContainsString('Solar Kit', $csvContent);
        $this->assertStringContainsString('Home Battery', $csvContent);
        $this->assertStringContainsString('Prepaid Grid Bundle', $csvContent);
        $this->assertStringContainsString('Northern Cluster', $csvContent);
        $this->assertStringContainsString('Abuja Site', $csvContent);

        $excel = $this->actingAs($user)->get('/api/export/customers?format=excel');
        $excel->assertStatus(200);
        $this->assertStringContainsString(AbstractExportService::XLSX_CONTENT_TYPE, (string) $excel->headers->get('content-type'));
    }
}

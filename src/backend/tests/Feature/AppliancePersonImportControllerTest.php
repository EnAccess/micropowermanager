<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Appliance;
use App\Models\AppliancePerson;
use Database\Factories\ApplianceFactory;
use Database\Factories\AppliancePersonFactory;
use Database\Factories\ApplianceTypeFactory;
use Database\Factories\Person\PersonFactory;
use Database\Factories\UserFactory;
use Tests\TestCase;

class AppliancePersonImportControllerTest extends TestCase {
    private function createAppliance(string $name): Appliance {
        $applianceType = ApplianceTypeFactory::new()->create();

        return ApplianceFactory::new()->create(['name' => $name, 'appliance_type_id' => $applianceType->id]);
    }

    public function testImportCreatesInstallmentAndEnergyServiceAppliancePeople(): void {
        $user = UserFactory::new()->create();
        $this->assignPermission($user, 'appliances');

        $installmentCustomer = PersonFactory::new()->create(['name' => 'Ada', 'surname' => 'Lovelace']);
        $energyServiceCustomer = PersonFactory::new()->create(['name' => 'Grace', 'surname' => 'Hopper']);
        $this->createAppliance('Solar Kit');

        $response = $this->actingAs($user)->postJson('/api/import/appliance-people', [
            'data' => [
                [
                    'customer_name' => 'Ada',
                    'customer_surname' => 'Lovelace',
                    'appliance_name' => 'Solar Kit',
                    'payment_type' => 'installment',
                    'total_cost' => 300,
                    'rate_count' => 3,
                    'rate_type' => 'monthly',
                    'first_payment_date' => '2026-01-01',
                ],
                [
                    'customer_name' => 'Grace',
                    'customer_surname' => 'Hopper',
                    'appliance_name' => 'Solar Kit',
                    'payment_type' => 'energy_service',
                    'minimum_payable_amount' => 500,
                    'price_per_day' => 50,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.success', true);
        $response->assertJsonPath('data.added_count', 2);

        $installment = AppliancePerson::query()->where('person_id', $installmentCustomer->id)->first();
        $this->assertNotNull($installment);
        $this->assertSame('installment', $installment->payment_type);
        $this->assertSame(3, $installment->rates()->count());
        $this->assertSame(300, (int) $installment->rates()->sum('rate_cost'));

        $energyService = AppliancePerson::query()->where('person_id', $energyServiceCustomer->id)->first();
        $this->assertNotNull($energyService);
        $this->assertSame('energy_service', $energyService->payment_type);
        $this->assertSame(0, (int) $energyService->total_cost);
        $this->assertSame(0, $energyService->rate_count);
        $this->assertSame(500, $energyService->minimum_payable_amount);
        $this->assertSame(50, $energyService->price_per_day);
        $this->assertSame(0, $energyService->rates()->count());
    }

    public function testImportReportsRowsWithMissingCustomerOrAppliance(): void {
        $user = UserFactory::new()->create();
        $this->assignPermission($user, 'appliances');

        PersonFactory::new()->create(['name' => 'Ada', 'surname' => 'Lovelace']);
        $this->createAppliance('Solar Kit');

        $response = $this->actingAs($user)->postJson('/api/import/appliance-people', [
            'data' => [
                [
                    'customer_name' => 'Nobody',
                    'customer_surname' => 'Here',
                    'appliance_name' => 'Solar Kit',
                    'payment_type' => 'installment',
                    'total_cost' => 100,
                    'rate_count' => 2,
                ],
                [
                    'customer_name' => 'Ada',
                    'customer_surname' => 'Lovelace',
                    'appliance_name' => 'Unknown Appliance',
                    'payment_type' => 'installment',
                    'total_cost' => 100,
                    'rate_count' => 2,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.success', false);
        $response->assertJsonPath('data.added_count', 0);
        $response->assertJsonPath('data.failed_count', 2);
        $this->assertSame(0, AppliancePerson::query()->count());
    }

    public function testImportRejectsARowWhoseDeviceAlreadyHasAnAppliancePerson(): void {
        $user = UserFactory::new()->create();
        $this->assignPermission($user, 'appliances');

        $person = PersonFactory::new()->create(['name' => 'Ada', 'surname' => 'Lovelace']);
        $appliance = $this->createAppliance('Solar Kit');
        AppliancePersonFactory::new()->create([
            'person_id' => $person->id,
            'appliance_id' => $appliance->id,
            'device_serial' => 'SER-DUP',
        ]);

        $response = $this->actingAs($user)->postJson('/api/import/appliance-people', [
            'data' => [[
                'customer_name' => 'Ada',
                'customer_surname' => 'Lovelace',
                'appliance_name' => 'Solar Kit',
                'payment_type' => 'installment',
                'total_cost' => 100,
                'rate_count' => 2,
                'device_serial' => 'SER-DUP',
            ]],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.success', false);
        $response->assertJsonPath('data.added_count', 0);
        $response->assertJsonPath('data.failed_count', 1);
        $this->assertSame(1, AppliancePerson::query()->where('device_serial', 'SER-DUP')->count());
    }
}

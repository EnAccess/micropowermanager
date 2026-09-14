<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Appliance;
use App\Models\AppliancePerson;
use App\Models\Person\Person;
use App\Services\ExportServices\AbstractExportService;
use Database\Factories\ApplianceFactory;
use Database\Factories\AppliancePersonFactory;
use Database\Factories\ApplianceRateFactory;
use Database\Factories\ApplianceTypeFactory;
use Database\Factories\MainSettingsFactory;
use Database\Factories\Person\PersonFactory;
use Database\Factories\UserFactory;
use Tests\TestCase;

class OutstandingDebtsExportControllerTest extends TestCase {
    public function testExportSkipsRatesWhoseLoanOrCustomerWasDeleted(): void {
        $user = UserFactory::new()->create();
        $this->assignPermission($user, 'exports');
        MainSettingsFactory::new()->create();

        $appliance = ApplianceFactory::new()->create([
            'name' => 'Solar Kit',
            'appliance_type_id' => ApplianceTypeFactory::new()->create()->id,
        ]);

        $this->createLoanWithOutstandingRate('Ada', 'Lovelace', $appliance);

        $deletedLoan = $this->createLoanWithOutstandingRate('Grace', 'Hopper', $appliance);
        $deletedLoan->delete();

        $loanOfDeletedCustomer = $this->createLoanWithOutstandingRate('Alan', 'Turing', $appliance);
        Person::query()->findOrFail($loanOfDeletedCustomer->person_id)->delete();

        $json = $this->actingAs($user)->getJson('/api/export/debts?format=json');
        $json->assertStatus(200);
        $json->assertJsonPath('meta.total', 1);
        $json->assertJsonPath('meta.currency', '€');

        /** @var array<int, array<string, mixed>> $data */
        $data = $json->json('data');
        $rows = collect($data)->keyBy('customer');

        $this->assertNull($rows->get('Grace Hopper'));
        $this->assertNull($rows->get('Alan Turing'));

        $healthyRow = $rows->get('Ada Lovelace');
        $this->assertNotNull($healthyRow);
        $this->assertSame('Solar Kit', $healthyRow['appliance']);
        $this->assertSame('100', $healthyRow['down_payment']);
        $this->assertSame('150', $healthyRow['remaining']);
        $this->assertSame('150', $healthyRow['total_paid']);
        $this->assertSame('150', $healthyRow['total_remaining']);

        $excel = $this->actingAs($user)->get('/api/export/debts');
        $excel->assertStatus(200);
        $this->assertStringContainsString(AbstractExportService::XLSX_CONTENT_TYPE, (string) $excel->headers->get('content-type'));
    }

    /**
     * A loan with a settled down payment rate and a single overdue rate that is
     * partially paid, so every reported total is non-zero and distinguishable.
     */
    private function createLoanWithOutstandingRate(string $name, string $surname, Appliance $appliance): AppliancePerson {
        $person = PersonFactory::new()->isCustomer()->create(['name' => $name, 'surname' => $surname]);
        $appliancePerson = AppliancePersonFactory::new()->create([
            'person_id' => $person->id,
            'appliance_id' => $appliance->id,
            'down_payment' => 100,
            'total_cost' => 300,
            'rate_count' => 2,
            'device_serial' => 'SER-'.$surname,
        ]);

        ApplianceRateFactory::new()->create([
            'appliance_person_id' => $appliancePerson->id,
            'rate_cost' => 100,
            'remaining' => 0,
            'due_date' => now()->subMonth()->toDateString(),
        ]);
        ApplianceRateFactory::new()->create([
            'appliance_person_id' => $appliancePerson->id,
            'rate_cost' => 200,
            'remaining' => 150,
            'due_date' => now()->subDays(7)->toDateString(),
        ]);

        return $appliancePerson;
    }
}

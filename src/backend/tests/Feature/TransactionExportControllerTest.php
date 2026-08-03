<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Transaction\Transaction;
use App\Services\ExportServices\AbstractExportService;
use Database\Factories\AgentTransactionFactory;
use Database\Factories\DeviceFactory;
use Database\Factories\Meter\MeterFactory;
use Database\Factories\Person\PersonFactory;
use Database\Factories\TransactionFactory;
use Database\Factories\UserFactory;
use Tests\TestCase;

class TransactionExportControllerTest extends TestCase {
    public function testExportRejectsFormatsOutsideTheAllowedSet(): void {
        $user = UserFactory::new()->create();
        $this->assignPermission($user, 'exports');

        $this->actingAs($user)->getJson('/api/export/transactions?format=xlsx')
            ->assertStatus(422)
            ->assertJsonValidationErrors('format');

        foreach (['excel', 'csv', 'json'] as $format) {
            $this->actingAs($user)->get("/api/export/transactions?format={$format}")->assertStatus(200);
        }
    }

    public function testExportOmitsTheCustomerNameWhenTheDeviceHasNoPerson(): void {
        $user = UserFactory::new()->create();
        $this->assignPermission($user, 'exports');

        $this->createTransaction('SER-WITH-PERSON', PersonFactory::new()->create(['name' => 'Ada', 'surname' => 'Lovelace'])->id);
        $this->createTransaction('SER-WITHOUT-PERSON', null);

        $json = $this->actingAs($user)->getJson('/api/export/transactions?format=json');
        $json->assertStatus(200);
        $json->assertJsonPath('meta.total', 2);
        $json->assertJsonPath('data.0.customer', '');
        $json->assertJsonPath('data.0.device_id', 'SER-WITHOUT-PERSON');
        $json->assertJsonPath('data.1.customer', 'Ada Lovelace');

        $csv = $this->actingAs($user)->get('/api/export/transactions?format=csv');
        $csv->assertStatus(200);
        $this->assertStringContainsString(AbstractExportService::CSV_CONTENT_TYPE, (string) $csv->headers->get('content-type'));

        $this->actingAs($user)->get('/api/export/transactions?format=excel')->assertStatus(200);
    }

    public function testExportToleratesTransactionsWithoutADevice(): void {
        $user = UserFactory::new()->create();
        $this->assignPermission($user, 'exports');

        $agentTransaction = AgentTransactionFactory::new()->create();
        TransactionFactory::new()->create([
            'message' => 'SER-UNKNOWN-DEVICE',
            'original_transaction_id' => $agentTransaction->id,
            'original_transaction_type' => 'agent_transaction',
        ]);

        $json = $this->actingAs($user)->getJson('/api/export/transactions?format=json');
        $json->assertStatus(200);
        $json->assertJsonPath('data.0.customer', '');
        $json->assertJsonPath('data.0.device_id', '');
        $json->assertJsonPath('data.0.device_type', '');

        $this->actingAs($user)->get('/api/export/transactions?format=excel')->assertStatus(200);
    }

    private function createTransaction(string $deviceSerial, ?int $personId): Transaction {
        $meter = MeterFactory::new()->create();
        DeviceFactory::new()->create([
            'device_serial' => $deviceSerial,
            'device_id' => $meter->id,
            'device_type' => 'meter',
            'person_id' => $personId,
        ]);

        $agentTransaction = AgentTransactionFactory::new()->create();

        return TransactionFactory::new()->create([
            'message' => $deviceSerial,
            'original_transaction_id' => $agentTransaction->id,
            'original_transaction_type' => 'agent_transaction',
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\TransactionSuccessfulEvent;
use App\Jobs\ProcessPayment;
use App\Models\Address\Address;
use App\Models\AgentBalanceHistory;
use App\Models\AgentCommission;
use App\Models\Device;
use App\Models\MpmPlugin;
use App\Models\Plugins;
use App\Models\Transaction\AgentTransaction;
use App\Models\Transaction\Transaction;
use App\Plugins\VodacomMzPaymentProvider\Http\Clients\VodacomMzApiClient;
use App\Plugins\VodacomMzPaymentProvider\Models\VodacomMzTransaction;
use Database\Factories\Meter\MeterFactory;
use Illuminate\Support\Facades\Queue;
use Tests\CreateEnvironments;
use Tests\TestCase;

class AgentAppVodacomMZProviderPaymentTest extends TestCase {
    use CreateEnvironments;

    public function testAgentInitiatesProviderTopUp(): void {
        $device = $this->createAgentWithCustomerDevice();
        $this->enableVodacom();
        $this->stubVodacomC2bPayment();

        $response = $this->actingAs($this->agent)->postJson(
            '/api/app/agents/transactions',
            [
                'device_serial' => $device->device_serial,
                'amount' => 500,
                'payment_provider' => MpmPlugin::VODACOM_MZ_PAYMENT_PROVIDER,
            ],
            ['device-id' => $this->agent->mobile_device_id],
        );

        $response->assertStatus(200);

        $transaction = Transaction::query()->findOrFail($response['data']['id']);
        $this->assertSame(Transaction::TYPE_ENERGY, $transaction->type);
        $this->assertSame($device->device_serial, $transaction->message);
        $this->assertSame($this->agent->id, $transaction->agent_id);
        $this->assertSame('vodacom_mz_transaction', $transaction->original_transaction_type);

        // The provider pushes the PIN prompt to the payer, so the sender is the customer's own
        // phone rather than the 'Agent-{id}' marker a cash collection carries.
        $this->assertSame($this->primaryPhoneOf($device), $transaction->sender);
        $this->assertSame(0, AgentTransaction::query()->count());

        Queue::assertPushed(ProcessPayment::class);
    }

    public function testProviderTopUpEarnsCommissionWithoutMovingBalance(): void {
        $device = $this->createAgentWithCustomerDevice();
        $this->enableVodacom();
        $this->stubVodacomC2bPayment();
        $balanceBefore = $this->agent->balance;
        $commissionRevenueBefore = $this->agent->commission_revenue;

        $transaction = $this->initiateTopUp($device, 500);
        event(new TransactionSuccessfulEvent($transaction));

        $agent = $this->agent->fresh();
        $this->assertSame((float) $balanceBefore, (float) $agent->balance);
        $this->assertSame(
            round($commissionRevenueBefore + 500 * $this->agentCommission->energy_commission, 2),
            round((float) $agent->commission_revenue, 2),
        );

        $rows = AgentBalanceHistory::query()->where('transaction_id', $transaction->id)->get();
        $this->assertCount(1, $rows);
        $this->assertSame(AgentCommission::RELATION_NAME, $rows->first()->trigger_type);
    }

    public function testProviderTopUpCommissionIsNotCreditedTwice(): void {
        $device = $this->createAgentWithCustomerDevice();
        $this->enableVodacom();
        $this->stubVodacomC2bPayment();

        $transaction = $this->initiateTopUp($device, 500);

        // A queue retry can replay the event for an already-credited transaction.
        event(new TransactionSuccessfulEvent($transaction));
        event(new TransactionSuccessfulEvent($transaction));

        $this->assertSame(1, AgentBalanceHistory::query()->where('transaction_id', $transaction->id)->count());
        $this->assertSame(
            round(500 * $this->agentCommission->energy_commission, 2),
            round((float) $this->agent->fresh()->commission_revenue, 2),
        );
    }

    public function testProviderTopUpIsNotBlockedByRiskBalance(): void {
        $device = $this->createAgentWithCustomerDevice();
        $this->enableVodacom();
        $this->stubVodacomC2bPayment();

        $amount = $this->agentCommission->risk_balance * 100;

        $response = $this->actingAs($this->agent)->postJson(
            '/api/app/agents/transactions',
            [
                'device_serial' => $device->device_serial,
                'amount' => $amount,
                'payment_provider' => MpmPlugin::VODACOM_MZ_PAYMENT_PROVIDER,
            ],
            ['device-id' => $this->agent->mobile_device_id],
        );

        $response->assertStatus(200);
        $this->assertSame(1, Transaction::query()->where('message', $device->device_serial)->count());
    }

    public function testProviderTopUpUsesPayerPhoneOverride(): void {
        $device = $this->createAgentWithCustomerDevice();
        $this->enableVodacom();
        $this->stubVodacomC2bPayment();

        $response = $this->actingAs($this->agent)->postJson(
            '/api/app/agents/transactions',
            [
                'device_serial' => $device->device_serial,
                'amount' => 500,
                'payment_provider' => MpmPlugin::VODACOM_MZ_PAYMENT_PROVIDER,
                'payer_phone' => '+258841234567',
            ],
            ['device-id' => $this->agent->mobile_device_id],
        );

        $response->assertStatus(200);
        $this->assertSame('+258841234567', Transaction::query()->findOrFail($response['data']['id'])->sender);
    }

    public function testProviderTopUpWithoutResolvablePayerPhoneIsRejected(): void {
        $device = $this->createAgentWithCustomerDevice();
        $this->enableVodacom();
        $this->stubVodacomC2bPayment();

        Address::query()->where('owner_id', $device->person_id)
            ->where('owner_type', 'person')
            ->update(['phone' => null]);

        $response = $this->actingAs($this->agent)->postJson(
            '/api/app/agents/transactions',
            [
                'device_serial' => $device->device_serial,
                'amount' => 500,
                'payment_provider' => MpmPlugin::VODACOM_MZ_PAYMENT_PROVIDER,
            ],
            ['device-id' => $this->agent->mobile_device_id],
        );

        $response->assertStatus(422);
        $this->assertSame(0, Transaction::query()->count());
        $this->assertSame(0, VodacomMzTransaction::query()->count());
    }

    public function testAgentTopUpForUnassignedDeviceIsRejected(): void {
        $this->createAgentWithCustomerDevice();
        $this->enableVodacom();
        $this->stubVodacomC2bPayment();

        $response = $this->actingAs($this->agent)->postJson(
            '/api/app/agents/transactions',
            [
                'device_serial' => 'MTR-DOES-NOT-EXIST',
                'amount' => 500,
                'payment_provider' => MpmPlugin::VODACOM_MZ_PAYMENT_PROVIDER,
            ],
            ['device-id' => $this->agent->mobile_device_id],
        );

        $response->assertStatus(422);
        $this->assertSame(0, Transaction::query()->count());
    }

    public function testAgentCannotPayWithProviderTheTenantHasNotEnabled(): void {
        $device = $this->createAgentWithCustomerDevice();
        Plugins::query()->create([
            'mpm_plugin_id' => MpmPlugin::VODACOM_MZ_PAYMENT_PROVIDER,
            'status' => Plugins::INACTIVE,
        ]);
        $apiClient = $this->stubVodacomC2bPayment();
        $apiClient->expects($this->never())->method('c2bPayment');

        $response = $this->actingAs($this->agent)->postJson(
            '/api/app/agents/transactions',
            [
                'device_serial' => $device->device_serial,
                'amount' => 500,
                'payment_provider' => MpmPlugin::VODACOM_MZ_PAYMENT_PROVIDER,
            ],
            ['device-id' => $this->agent->mobile_device_id],
        );

        $response->assertStatus(422);
        $this->assertSame(0, Transaction::query()->count());
    }

    public function testAgentListsPaymentProviders(): void {
        $this->createAgentWithCustomerDevice();
        $this->enableVodacom();

        // Active, but inbound-only: it cannot initiate a payment, so it must not be offered.
        Plugins::query()->create([
            'mpm_plugin_id' => MpmPlugin::SWIFTA_PAYMENT_PROVIDER,
            'status' => Plugins::ACTIVE,
        ]);
        Plugins::query()->create([
            'mpm_plugin_id' => MpmPlugin::PAYSTACK_PAYMENT_PROVIDER,
            'status' => Plugins::INACTIVE,
        ]);

        $response = $this->actingAs($this->agent)->getJson('/api/app/agents/payment-providers');

        $response->assertStatus(200);
        $this->assertCount(1, $response['data']);
        $this->assertSame(MpmPlugin::VODACOM_MZ_PAYMENT_PROVIDER, $response['data'][0]['id']);
    }

    public function testAgentPollsProviderPaymentStatus(): void {
        $device = $this->createAgentWithCustomerDevice();
        $this->enableVodacom();
        $this->stubVodacomC2bPayment();

        $transaction = $this->initiateTopUp($device, 500);

        $response = $this->actingAs($this->agent)
            ->getJson("/api/app/agents/transactions/{$transaction->id}/status");

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'status' => 'processing',
                'processed' => false,
                'transaction_id' => $transaction->id,
            ],
        ]);
    }

    public function testPaymentStatusReportsFailedForARejectedProviderTransaction(): void {
        $device = $this->createAgentWithCustomerDevice();
        $this->enableVodacom();
        $this->stubVodacomC2bPayment();

        $transaction = $this->initiateTopUp($device, 500);
        $transaction->originalTransaction()->first()->update(['status' => VodacomMzTransaction::STATUS_FAILED]);

        $response = $this->actingAs($this->agent)
            ->getJson("/api/app/agents/transactions/{$transaction->id}/status");

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'failed');
        $response->assertJsonPath('data.processed', false);
    }

    public function testAgentCannotPollTransactionOfAnotherMiniGrid(): void {
        $device = $this->createAgentWithCustomerDevice();
        $this->enableVodacom();
        $this->stubVodacomC2bPayment();

        $transaction = $this->initiateTopUp($device, 500);

        $this->createMiniGrid(2);
        $this->createAgent();
        $otherAgent = $this->agents[1];
        $otherAgent->update(['mini_grid_id' => $this->miniGrids[1]->id]);

        $response = $this->actingAs($otherAgent)
            ->getJson("/api/app/agents/transactions/{$transaction->id}/status");

        $response->assertStatus(404);
    }

    public function testAgentTransactionListIncludesProviderPayments(): void {
        $device = $this->createAgentWithCustomerDevice();
        $this->enableVodacom();
        $this->stubVodacomC2bPayment();

        $transaction = $this->initiateTopUp($device, 500);

        $response = $this->actingAs($this->agent)->getJson('/api/app/agents/transactions');

        $response->assertStatus(200);
        $this->assertContains($transaction->id, array_column($response['data'], 'id'));
    }

    private function createAgentWithCustomerDevice(): Device {
        Queue::fake();
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createMeterType();
        $this->createMeterManufacturer();
        $this->createMeterTariff();
        $this->createConnectionGroup();
        $this->createConnectionType();
        $this->createPerson();
        $this->createAgentCommission();
        $this->createAgent();

        // CreateEnvironments::createMeter() never assigns $this->meter, which createMeterDevice()
        // relies on, so the meter this device points at is built here instead.
        $this->meter = MeterFactory::new()->create([
            'meter_type_id' => $this->meterType->id,
            'in_use' => true,
            'manufacturer_id' => $this->manufacturers[0]->id,
            'serial_number' => 'MTR-PROVIDER-001',
            'connection_type_id' => $this->connectionType->id,
            'connection_group_id' => $this->connectionGroup->id,
            'tariff_id' => $this->meterTariffs[0]->id,
        ]);
        $this->createMeterDevice($this->person);

        return $this->meterDevice;
    }

    private function enableVodacom(): void {
        Plugins::query()->create([
            'mpm_plugin_id' => MpmPlugin::VODACOM_MZ_PAYMENT_PROVIDER,
            'status' => Plugins::ACTIVE,
        ]);
    }

    private function stubVodacomC2bPayment(): VodacomMzApiClient {
        $apiClient = $this->createMock(VodacomMzApiClient::class);
        $apiClient->method('c2bPayment')->willReturn([
            'output_ResponseCode' => VodacomMzApiClient::RESPONSE_SUCCESS,
            'output_ConversationID' => 'conversation-1',
            'output_TransactionID' => 'transaction-1',
        ]);
        $this->app->instance(VodacomMzApiClient::class, $apiClient);

        return $apiClient;
    }

    private function initiateTopUp(Device $device, float $amount): Transaction {
        $response = $this->actingAs($this->agent)->postJson(
            '/api/app/agents/transactions',
            [
                'device_serial' => $device->device_serial,
                'amount' => $amount,
                'payment_provider' => MpmPlugin::VODACOM_MZ_PAYMENT_PROVIDER,
            ],
            ['device-id' => $this->agent->mobile_device_id],
        );
        $response->assertStatus(200);

        return Transaction::query()->findOrFail($response['data']['id']);
    }

    private function primaryPhoneOf(Device $device): string {
        return (string) Address::query()->where('owner_id', $device->person_id)
            ->where('owner_type', 'person')
            ->where('is_primary', 1)
            ->firstOrFail()
            ->phone;
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\TransactionSuccessfulEvent;
use App\Jobs\ProcessPayment;
use App\Models\AgentBalanceHistory;
use App\Models\Appliance;
use App\Models\AppliancePerson;
use App\Models\ApplianceRate;
use App\Models\MpmPlugin;
use App\Models\Plugins;
use App\Models\Transaction\AgentTransaction;
use App\Models\Transaction\Transaction;
use App\Plugins\VodacomMzPaymentProvider\Http\Clients\VodacomMzApiClient;
use Database\Factories\AppliancePersonFactory;
use Database\Factories\ApplianceTypeFactory;
use Illuminate\Support\Facades\Queue;
use Tests\CreateEnvironments;
use Tests\TestCase;

class AgentAppliancePaymentTest extends TestCase {
    use CreateEnvironments;

    public function testAgentPaysInstallmentInCash(): void {
        $appliancePerson = $this->createSoldApplianceForAgentCustomer();

        $response = $this->actingAs($this->agent)
            ->postJson("/api/app/agents/appliances/{$appliancePerson->id}/payment", ['amount' => 100]);

        $response->assertStatus(200);

        $transaction = Transaction::query()->findOrFail($response['data']['transaction_id']);
        $this->assertSame(Transaction::TYPE_DEFERRED_PAYMENT, $transaction->type);
        $this->assertSame('Agent-'.$this->agent->id, $transaction->sender);
        $this->assertSame((string) $appliancePerson->id, $transaction->message);
        $this->assertNull($transaction->agent_id);

        // Cash the agent collected is money they now hold, so it must be an AgentTransaction.
        $this->assertSame('agent_transaction', $transaction->original_transaction_type);
        $this->assertSame(1, AgentTransaction::query()->where('agent_id', $this->agent->id)->count());

        Queue::assertPushed(ProcessPayment::class);
    }

    public function testAgentPaysInstallmentWithProvider(): void {
        $appliancePerson = $this->createSoldApplianceForAgentCustomer();
        $this->enableVodacom();
        $this->stubVodacomC2bPayment();

        $response = $this->actingAs($this->agent)->postJson(
            "/api/app/agents/appliances/{$appliancePerson->id}/payment",
            ['amount' => 100, 'payment_provider' => MpmPlugin::VODACOM_MZ_PAYMENT_PROVIDER],
        );

        $response->assertStatus(200);

        $transaction = Transaction::query()->findOrFail($response['data']['transaction_id']);
        $this->assertSame('vodacom_mz_transaction', $transaction->original_transaction_type);
        $this->assertSame($this->agent->id, $transaction->agent_id);
        $this->assertSame((string) $this->person->addresses()->where('is_primary', 1)->first()->phone, $transaction->sender);
        $this->assertSame(0, AgentTransaction::query()->count());
    }

    public function testProviderInstallmentEarnsNoCommission(): void {
        $appliancePerson = $this->createSoldApplianceForAgentCustomer();
        $this->enableVodacom();
        $this->stubVodacomC2bPayment();
        $balanceBefore = (float) $this->agent->balance;
        $commissionRevenueBefore = (float) $this->agent->commission_revenue;

        $response = $this->actingAs($this->agent)->postJson(
            "/api/app/agents/appliances/{$appliancePerson->id}/payment",
            ['amount' => 100, 'payment_provider' => MpmPlugin::VODACOM_MZ_PAYMENT_PROVIDER],
        );
        $response->assertStatus(200);

        $transaction = Transaction::query()->findOrFail($response['data']['transaction_id']);
        event(new TransactionSuccessfulEvent($transaction));

        // The sale already commissioned the appliance's full cost, so collecting an installment
        // earns nothing on top of it.
        $agent = $this->agent->fresh();
        $this->assertSame($balanceBefore, (float) $agent->balance);
        $this->assertSame($commissionRevenueBefore, (float) $agent->commission_revenue);
        $this->assertSame(0, AgentBalanceHistory::query()->where('transaction_id', $transaction->id)->count());
    }

    public function testAgentCannotPayInstallmentForCustomerOfAnotherMiniGrid(): void {
        $appliancePerson = $this->createSoldApplianceForAgentCustomer();

        $this->createMiniGrid(2);
        $this->createAgent();
        $otherAgent = $this->agents[1];
        $otherAgent->update(['mini_grid_id' => $this->miniGrids[1]->id]);

        $response = $this->actingAs($otherAgent)
            ->postJson("/api/app/agents/appliances/{$appliancePerson->id}/payment", ['amount' => 100]);

        $response->assertStatus(404);
        $this->assertSame(0, Transaction::query()->count());
    }

    public function testInstallmentAmountBelowInstallmentCostIsRejected(): void {
        $appliancePerson = $this->createSoldApplianceForAgentCustomer();

        $response = $this->actingAs($this->agent)
            ->postJson("/api/app/agents/appliances/{$appliancePerson->id}/payment", ['amount' => 50]);

        $response->assertStatus(422);
        $this->assertSame(0, Transaction::query()->count());
    }

    private function createSoldApplianceForAgentCustomer(): AppliancePerson {
        Queue::fake();
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createPerson();
        $this->createAgentCommission();
        $this->createAgent();

        $applianceType = ApplianceTypeFactory::new()->create();
        $appliance = Appliance::query()->create([
            'name' => 'Test Appliance',
            'price' => 1000,
            'appliance_type_id' => $applianceType->id,
        ]);

        /** @var AppliancePerson $appliancePerson */
        $appliancePerson = AppliancePersonFactory::new()->create([
            'appliance_id' => $appliance->id,
            'person_id' => $this->person->id,
            'total_cost' => 200,
            'rate_count' => 2,
            'down_payment' => 0,
        ]);

        ApplianceRate::query()->create([
            'appliance_person_id' => $appliancePerson->id,
            'rate_cost' => 100,
            'remaining' => 100,
            'remind' => 0,
            'due_date' => now()->addMonth(),
        ]);

        return $appliancePerson;
    }

    private function enableVodacom(): void {
        Plugins::query()->create([
            'mpm_plugin_id' => MpmPlugin::VODACOM_MZ_PAYMENT_PROVIDER,
            'status' => Plugins::ACTIVE,
        ]);
    }

    private function stubVodacomC2bPayment(): void {
        $apiClient = $this->createMock(VodacomMzApiClient::class);
        $apiClient->method('c2bPayment')->willReturn([
            'output_ResponseCode' => VodacomMzApiClient::RESPONSE_SUCCESS,
            'output_ConversationID' => 'conversation-1',
            'output_TransactionID' => 'transaction-1',
        ]);
        $this->app->instance(VodacomMzApiClient::class, $apiClient);
    }
}

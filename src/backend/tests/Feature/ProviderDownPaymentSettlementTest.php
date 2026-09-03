<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\ApplianceTransactionProcessor;
use App\Jobs\ProcessPayment;
use App\Jobs\TokenProcessor;
use App\Models\Address\Address;
use App\Models\Agent;
use App\Models\AgentBalanceHistory;
use App\Models\AgentCommission;
use App\Models\Appliance;
use App\Models\AppliancePerson;
use App\Models\ApplianceRate;
use App\Models\ApplianceType;
use App\Models\Device;
use App\Models\MpmPlugin;
use App\Models\PaymentHistory;
use App\Models\Plugins;
use App\Models\SolarHomeSystem;
use App\Models\Transaction\Transaction;
use App\Plugins\VodacomMzPaymentProvider\Http\Clients\VodacomMzApiClient;
use Illuminate\Support\Facades\Queue;
use Tests\CreateEnvironments;
use Tests\TestCase;

/**
 * A provider down payment settles in a queued job, so the commission credit and the payment
 * distribution only exist once that job runs. Faking the queue and asserting it was dispatched
 * leaves both unverified, which is how the double-credited down payment went unnoticed.
 */
class ProviderDownPaymentSettlementTest extends TestCase {
    use CreateEnvironments;

    private const float APPLIANCE_COST = 1000.0;
    private const float DOWN_PAYMENT = 200.0;
    private const int TENURE = 5;

    public function testSettlingDownPaymentRecordsItAsAPaidRateNotSpreadOverInstallments(): void {
        $transaction = $this->sellApplianceWithProviderDownPayment(paygo: false);

        $this->settle($transaction);

        $rates = ApplianceRate::query()
            ->where('appliance_person_id', AppliancePerson::query()->firstOrFail()->id)
            ->get();

        $this->assertSame(self::APPLIANCE_COST, (float) $rates->sum('rate_cost'));
        $this->assertSame(self::APPLIANCE_COST - self::DOWN_PAYMENT, (float) $rates->sum('remaining'));

        $paidRates = $rates->where('remaining', 0);
        $this->assertCount(1, $paidRates);
        $this->assertSame(self::DOWN_PAYMENT, (float) $paidRates->first()->rate_cost);
    }

    public function testSettlingDownPaymentRecordsOneDownPaymentHistory(): void {
        $transaction = $this->sellApplianceWithProviderDownPayment(paygo: false);

        $this->settle($transaction);

        $histories = PaymentHistory::query()->where('transaction_id', $transaction->id)->get();
        $this->assertCount(1, $histories);
        $this->assertSame(Transaction::TYPE_DOWN_PAYMENT, $histories->first()->payment_type);
        $this->assertSame(self::DOWN_PAYMENT, (float) $histories->first()->amount);
    }

    public function testSettlingDownPaymentCreditsTheAgentCommissionThroughTheRealPipeline(): void {
        $transaction = $this->sellApplianceWithProviderDownPayment(paygo: false);
        $agent = Agent::query()->findOrFail($transaction->agent_id);
        $balanceBefore = (float) $agent->balance;

        $this->settle($transaction);

        $agent = $agent->fresh();
        $this->assertSame($balanceBefore, (float) $agent->balance);
        $this->assertSame(
            round(self::APPLIANCE_COST * $this->agentCommission->appliance_commission, 2),
            round((float) $agent->commission_revenue, 2),
        );

        $rows = AgentBalanceHistory::query()->where('transaction_id', $transaction->id)->get();
        $this->assertCount(1, $rows);
        $this->assertSame(AgentCommission::RELATION_NAME, $rows->first()->trigger_type);
    }

    public function testSettlingDownPaymentSmallerThanOneInstallmentIsAccepted(): void {
        $transaction = $this->sellApplianceWithProviderDownPayment(paygo: false, downPayment: 40.0);

        $this->settle($transaction);

        $this->assertSame(1, PaymentHistory::query()->where('transaction_id', $transaction->id)->count());
        $this->assertSame(40.0, (float) ApplianceRate::query()->where('remaining', 0)->firstOrFail()->rate_cost);
    }

    public function testPaygoSettlementDefersTheSuccessEventToTokenProcessor(): void {
        $transaction = $this->sellApplianceWithProviderDownPayment(paygo: true);

        $this->settle($transaction);

        Queue::assertPushed(TokenProcessor::class);
        $this->assertSame(0, AgentBalanceHistory::query()->where('transaction_id', $transaction->id)->count());
        $this->assertSame(
            self::DOWN_PAYMENT,
            (float) ApplianceRate::query()->where('remaining', 0)->firstOrFail()->rate_cost,
        );
    }

    private function settle(Transaction $transaction): void {
        new ApplianceTransactionProcessor($this->companyId, $transaction->id)->handle();
    }

    private function sellApplianceWithProviderDownPayment(bool $paygo, float $downPayment = self::DOWN_PAYMENT): Transaction {
        Queue::fake();
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createPerson();
        $this->createAgentCommission();
        $this->createAgent();
        $this->createMeterManufacturer();

        Plugins::query()->create([
            'mpm_plugin_id' => MpmPlugin::VODACOM_MZ_PAYMENT_PROVIDER,
            'status' => Plugins::ACTIVE,
        ]);
        $apiClient = $this->createMock(VodacomMzApiClient::class);
        $apiClient->method('c2bPayment')->willReturn([
            'output_ResponseCode' => VodacomMzApiClient::RESPONSE_SUCCESS,
        ]);
        $this->app->instance(VodacomMzApiClient::class, $apiClient);

        $applianceType = ApplianceType::query()->create([
            'name' => $paygo ? 'Solar Home System' : 'Kettle',
            'paygo_enabled' => $paygo,
        ]);
        $appliance = Appliance::query()->create([
            'name' => 'Test Appliance',
            'price' => self::APPLIANCE_COST,
            'appliance_type_id' => $applianceType->id,
        ]);
        $assignedAppliance = $this->agent->assignedAppliance()->create([
            'user_id' => $this->user->id,
            'appliance_id' => $appliance->id,
            'cost' => self::APPLIANCE_COST,
        ]);

        $response = $this->actingAs($this->agent)->postJson('/api/app/agents/appliances', [
            'person_id' => $this->person->id,
            'agent_assigned_appliance_id' => $assignedAppliance->id,
            'down_payment' => $downPayment,
            'tenure' => self::TENURE,
            'first_payment_date' => now()->addMonth()->toDateString(),
            'device_serial' => $this->createDeviceFor($appliance),
            'payment_provider' => MpmPlugin::VODACOM_MZ_PAYMENT_PROVIDER,
        ]);
        $response->assertStatus(201);
        Queue::assertPushed(ProcessPayment::class);

        return Transaction::query()->findOrFail($response['data']['transaction_id']);
    }

    private function createDeviceFor(Appliance $appliance): string {
        $serial = 'SHS-SETTLE-0001';
        $solarHomeSystem = SolarHomeSystem::query()->create([
            'serial_number' => $serial,
            'manufacturer_id' => $this->manufacturers[0]->id,
            'appliance_id' => $appliance->id,
        ]);
        Device::query()->create([
            'person_id' => $this->person->id,
            'device_id' => $solarHomeSystem->id,
            'device_type' => SolarHomeSystem::class,
            'device_serial' => $serial,
        ]);

        Address::query()->where('owner_id', $this->person->id)
            ->where('owner_type', 'person')
            ->update(['is_primary' => 1]);

        return $serial;
    }
}

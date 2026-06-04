<?php

namespace Tests\Feature;

use App\Models\AgentBalanceHistory;
use App\Services\AgentService;
use Database\Factories\AgentBalanceHistoryFactory;
use Database\Factories\ApplianceFactory;
use Database\Factories\AppliancePersonFactory;
use Database\Factories\ApplianceTypeFactory;
use Database\Factories\Person\PersonFactory;
use Tests\CreateEnvironments;
use Tests\TestCase;

class AgentServiceMetricsTest extends TestCase {
    use CreateEnvironments;

    public function testGetAllExposesSalesCustomerAndActiveMetrics(): void {
        $this->prepareAgent();

        $customerA = PersonFactory::new()->create();
        $customerB = PersonFactory::new()->create();
        $applianceType = ApplianceTypeFactory::new()->create();
        $appliance = ApplianceFactory::new()->create(['appliance_type_id' => $applianceType->id]);

        // Two sales to the same customer and one to another: 3 sales, 2 customers.
        AppliancePersonFactory::new()->count(2)->create([
            'creator_type' => $this->agent->getMorphClass(),
            'creator_id' => $this->agent->id,
            'person_id' => $customerA->id,
            'appliance_id' => $appliance->id,
        ]);
        AppliancePersonFactory::new()->create([
            'creator_type' => $this->agent->getMorphClass(),
            'creator_id' => $this->agent->id,
            'person_id' => $customerB->id,
            'appliance_id' => $appliance->id,
        ]);

        AgentBalanceHistoryFactory::new()->create(['agent_id' => $this->agent->id]);

        $agent = app(AgentService::class)->getAll()->firstWhere('id', $this->agent->id);

        $this->assertSame(3, (int) $agent->sales_count);
        $this->assertSame(2, (int) $agent->customer_count);
        $this->assertTrue((bool) $agent->is_active);
    }

    public function testGetAllMarksAgentInactiveWithoutRecentActivity(): void {
        $this->prepareAgent();

        $history = AgentBalanceHistoryFactory::new()->create(['agent_id' => $this->agent->id]);
        AgentBalanceHistory::query()->where('id', $history->id)->update([
            'created_at' => now()->subDays(AgentService::ACTIVE_WINDOW_DAYS + 1),
        ]);

        $agent = app(AgentService::class)->getAll()->firstWhere('id', $this->agent->id);

        $this->assertSame(0, (int) $agent->sales_count);
        $this->assertSame(0, (int) $agent->customer_count);
        $this->assertFalse((bool) $agent->is_active);
    }

    private function prepareAgent(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createAgentCommission();
        $this->createAgent();
    }
}

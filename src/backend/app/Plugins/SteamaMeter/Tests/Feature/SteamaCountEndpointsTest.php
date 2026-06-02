<?php

namespace App\Plugins\SteamaMeter\Tests\Feature;

use App\Plugins\SteamaMeter\Models\SteamaAgent;
use App\Plugins\SteamaMeter\Models\SteamaCustomer;
use App\Plugins\SteamaMeter\Models\SteamaMeter;
use App\Plugins\SteamaMeter\Models\SteamaSite;
use Database\Factories\UserFactory;
use Tests\TestCase;

class SteamaCountEndpointsTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        $this->actingAs(UserFactory::new()->create());
    }

    public function testCountEndpointsReturnTheNumberOfSyncedRecords(): void {
        SteamaSite::query()->create(['site_id' => 1, 'mpm_mini_grid_id' => 1]);

        SteamaCustomer::query()->create(['site_id' => 1, 'user_type_id' => 1, 'customer_id' => 10, 'mpm_customer_id' => 10]);
        SteamaCustomer::query()->create(['site_id' => 1, 'user_type_id' => 1, 'customer_id' => 11, 'mpm_customer_id' => 11]);

        SteamaMeter::query()->create(['meter_id' => 1, 'customer_id' => 10, 'mpm_meter_id' => 1]);

        SteamaAgent::query()->create(['site_id' => 1, 'agent_id' => 1, 'mpm_agent_id' => 1, 'is_credit_limited' => 0, 'credit_balance' => 0]);
        SteamaAgent::query()->create(['site_id' => 1, 'agent_id' => 2, 'mpm_agent_id' => 2, 'is_credit_limited' => 0, 'credit_balance' => 0]);

        $this->assertSame(1, $this->getJson('/api/steama-meters/steama-site/count')->assertOk()->json());
        $this->assertSame(2, $this->getJson('/api/steama-meters/steama-customer/count')->assertOk()->json());
        $this->assertSame(1, $this->getJson('/api/steama-meters/steama-meter/count')->assertOk()->json());
        $this->assertSame(2, $this->getJson('/api/steama-meters/steama-agent/count')->assertOk()->json());
    }
}

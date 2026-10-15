<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Factories\Person\PersonFactory;
use Tests\CreateEnvironments;
use Tests\TestCase;

class AgentPersonTypeTest extends TestCase {
    use CreateEnvironments;

    public function testBackfillMarksPeopleWithAnAgentAsAgentType(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createAgentCommission();
        $this->createAgent();
        $customer = PersonFactory::new()->create();

        $migration = require database_path('migrations/tenant/2026_10_13_000000_backfill_agent_type_on_people.php');
        $migration->up();

        $this->assertTrue($this->agent->person->fresh()->isAgent());
        $this->assertFalse($customer->fresh()->isAgent());
    }

    public function testPersonDetailIncludesAgent(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createAgentCommission();
        $this->createAgent();

        $response = $this->actingAs($this->user)->getJson("/api/people/{$this->agent->person_id}");

        $response->assertOk()->assertJsonPath('data.agent.id', $this->agent->id);
    }
}

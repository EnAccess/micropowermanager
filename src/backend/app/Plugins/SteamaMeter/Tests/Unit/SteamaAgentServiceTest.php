<?php

namespace App\Plugins\SteamaMeter\Tests\Unit;

use App\Models\Agent;
use App\Models\AgentCommission;
use App\Models\City;
use App\Models\MiniGrid;
use App\Plugins\SteamaMeter\Models\SteamaSite;
use App\Plugins\SteamaMeter\Services\SteamaAgentService;
use Database\Factories\ClusterFactory;
use Database\Factories\UserFactory;
use Tests\TestCase;

class SteamaAgentServiceTest extends TestCase {
    private const int SITE_ID = 7;

    public function testCreateRelatedAgentPersistsTheAgentWithItsPersonName(): void {
        $cluster = ClusterFactory::new()->create(['manager_id' => UserFactory::new()->create()->id]);
        $miniGrid = MiniGrid::query()->create(['name' => 'Test Grid', 'cluster_id' => $cluster->id]);
        City::query()->create(['name' => 'Test City', 'mini_grid_id' => $miniGrid->id, 'country_id' => 0]);
        SteamaSite::query()->create([
            'site_id' => self::SITE_ID,
            'mpm_mini_grid_id' => $miniGrid->id,
            'hash' => 'hash',
        ]);
        AgentCommission::query()->create([
            'name' => 'Steama Agent Comission',
            'energy_commission' => 0,
            'appliance_commission' => 0,
            'risk_balance' => 0,
        ]);

        $agent = resolve(SteamaAgentService::class)->createRelatedAgent([
            'id' => 1,
            'first_name' => 'Bak',
            'last_name' => 'Steama',
            'telephone' => '+255712345678',
            'site' => self::SITE_ID,
            'site_name' => 'Test City',
        ]);

        $this->assertInstanceOf(Agent::class, $agent);
        $this->assertEquals('Bak', $agent->person->name);
        $this->assertEquals($miniGrid->id, $agent->mini_grid_id);
        $this->assertEquals('StmAgent1steama.co', $agent->email);
    }
}

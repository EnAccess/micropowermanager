<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\AgentAssignedAppliances;
use App\Models\AgentSoldAppliance;
use App\Models\Asset;
use App\Models\Person\Person;
use App\Services\AgentSoldApplianceService;
use Illuminate\Database\Seeder;

class AgentApplianceSalesSeeder extends Seeder {
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        // Fetch Existing Agents
        $agents = Agent::all();
        if ($agents->isEmpty()) {
            $this->command->warn('No existing agents found. Skipping seeder.');

            return;
        }

        // Fetch Existing Customers
        $customers = Person::where('is_customer', true)->get();
        if ($customers->isEmpty()) {
            $this->command->warn('No existing customers found. Skipping seeder.');

            return;
        }

        // Fetch Existing Assets
        $assets = Asset::all();
        if ($assets->isEmpty()) {
            $this->command->warn('No existing assets found. Skipping seeder.');

            return;
        }

        // Assign Assets to Agents
        $assignedAppliances = $agents->flatMap(function ($agent) use ($assets, $customers) {
            return AgentAssignedAppliances::factory()->count(3)->create([
                'agent_id' => $agent->id,
                'appliance_id' => $assets->random()->id,  // Use existing assets
                'user_id' => $customers->random()->id,    // Use existing customers
            ]);
        });

        // Simulate Sales
        $assignedAppliances->each(function ($assignedAppliance) use ($customers) {
            $soldAppliance = AgentSoldAppliance::factory()->create([
                'agent_assigned_appliance_id' => $assignedAppliance->id,
                'person_id' => $customers->random()->id,
            ]);
            // proccess sales
            $agentSoldApplianceService = app()->make(AgentSoldApplianceService::class);
            $agentSoldApplianceService->processSaleFromRequest($soldAppliance, [
                'person_id' => $customers->random()->id,
                'first_payment_date' => now(),
                'tenure' => 12,
                'down_payment' => 1000,
            ]);
        });

        $this->command->info('Agent Appliance Sales Seeded Successfully!');
    }
}

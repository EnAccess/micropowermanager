<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\AgentAssignedAppliances;
use App\Models\AgentSoldAppliance;
use App\Models\Appliance;
use App\Models\ApplianceType;
use App\Models\Device;
use App\Models\Manufacturer;
use App\Models\Person\Person;
use App\Models\SolarHomeSystem;
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

        // Fetch Existing Appliances
        $applianceType = ApplianceType::where('name', 'Solar Home System')->first();
        $appliances = Appliance::where('appliance_type_id', $applianceType->id)->get();
        if ($appliances->isEmpty()) {
            $this->command->warn('No existing appliances found. Skipping seeder.');

            return;
        }

        // Assign 2 unique Appliances to each Agent
        $assignedAppliances = $agents->flatMap(function ($agent) use ($appliances, $customers) {
            return collect($appliances->shuffle()->take(2))->map(function ($appliance) use ($agent, $customers) {
                return AgentAssignedAppliances::factory()->create([
                    'agent_id' => $agent->id,
                    'appliance_id' => $appliance->id,
                    'user_id' => $customers->random()->id,
                ]);
            });
        });

        // Simulate Sales
        $assignedAppliances->each(function ($assignedAppliance) use ($customers, $appliances) {
            $soldAppliance = AgentSoldAppliance::factory()->create([
                'agent_assigned_appliance_id' => $assignedAppliance->id,
                'person_id' => $customers->random()->id,
            ]);

            $solarHomeSystem = SolarHomeSystem::factory()
                ->for($appliances->random(), 'appliance')
                ->for(Manufacturer::where('type', 'shs')->get()->random())
                ->create();

            $device = Device::factory()
                ->for($solarHomeSystem, 'device')
                ->create([
                    'device_serial' => $solarHomeSystem->serial_number,
                ]);
            // proccess sales
            $agentSoldApplianceService = app()->make(AgentSoldApplianceService::class);
            $agentSoldApplianceService->processSaleFromRequest($soldAppliance, [
                'person_id' => $customers->random()->id,
                'first_payment_date' => now(),
                'tenure' => 12,
                'down_payment' => 1000,
                'device_serial' => $device->device_serial,
            ]);
        });

        $this->command->info('Agent Appliance Sales Seeded Successfully!');
    }
}

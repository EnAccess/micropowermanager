<?php

namespace Database\Seeders;

use App\Models\Address\Address;
use App\Models\GeographicalInformation;
use App\Models\MaintenanceUsers;
use App\Models\MiniGrid;
use App\Models\Person\Person;
use App\Models\User;
use Database\Factories\TicketFactory;
use Database\Factories\TicketOutsourceFactory;
use Database\Factories\TicketUserFactory;
use Illuminate\Console\View\Components\Info;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Inensus\Ticket\Models\TicketCategory;
use MPM\DatabaseProxy\DatabaseProxyManagerService;

class TicketSeeder extends Seeder {
    public function __construct(
        private DatabaseProxyManagerService $databaseProxyManagerService,
    ) {
        $this->databaseProxyManagerService->buildDatabaseConnectionDemoCompany();
    }

    private $amount = 100;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        (new Info($this->command->getOutput()))->render(
            "Running TransactionSeeder to generate $this->amount tickets. This may take some time."
        );

        // Create Ticket categories
        TicketCategory::factory()
            ->count(12)
            ->sequence(
                [
                    'label_name' => 'Payments/Top Up Issue',
                    'label_color' => 'yellow',
                    'out_source' => 0,
                ],
                [
                    'label_name' => 'No Power/Power Went OFF',
                    'label_color' => 'red',
                    'out_source' => 0,
                ],
                [
                    'label_name' => 'Installation Issue',
                    'label_color' => 'sky',
                    'out_source' => 0,
                ],
                [
                    'label_name' => 'Welcome Call',
                    'label_color' => 'pink',
                    'out_source' => 0,
                ],
                [
                    'label_name' => 'Technical or Software Issue',
                    'label_color' => 'lime',
                    'out_source' => 0,
                ],
                [
                    'label_name' => 'Installing Meters',
                    'label_color' => 'purple',
                    'out_source' => 1,
                ],
                [
                    'label_name' => 'System Troubleshoot',
                    'label_color' => 'black',
                    'out_source' => 1,
                ],
                [
                    'label_name' => 'Service Line Installation',
                    'label_color' => 'pink',
                    'out_source' => 1,
                ],
                [
                    'label_name' => 'In-door wiring',
                    'label_color' => 'lime',
                    'out_source' => 1,
                ],
                [
                    'label_name' => 'Cleaning Panel/ Cutting grass',
                    'label_color' => 'nocolor',
                    'out_source' => 1,
                ],
                [
                    'label_name' => 'Monthly Installment Follow up',
                    'label_color' => 'yellow',
                    'out_source' => 1,
                ],
                [
                    'label_name' => 'Meter Replacement',
                    'label_color' => 'nocolor',
                    'out_source' => 1,
                ],
            )
            ->create();

        // Create Maintenance Users

        // Get available MiniGrids
        $minigrids = MiniGrid::all();

        // For each Mini-Grid we create one Maintenance User
        foreach ($minigrids as $minigrid) {
            $village = $minigrid->cities()->get()->random();

            $person = Person::factory()
                ->isMaintenanceUser($village->name)
                ->has(
                    Address::factory()
                        ->for($village)
                        ->has(
                            GeographicalInformation::factory()
                                ->state(function (array $attributes, Address $address) {
                                    return ['points' => $address->city->location->points];
                                })
                                ->randomizePointsInVillage(),
                            'geo'
                        )
                )
                ->create();

            // Make the person a Maintenance User
            $maintenanceUser = MaintenanceUsers::factory()
                ->for($minigrid)
                ->for($person)
                ->create();

            // Find the MiniGrid's Agents and make them all Maintenance Users
            $agents = $minigrid->agents()->get();

            foreach ($agents as $agent) {
                $agentPerson = $agent->person()->first();

                if ($agentPerson) {
                    $maintenanceUserAgent = MaintenanceUsers::factory()
                        ->for($minigrid)
                        ->for($agentPerson)
                        ->create();
                }
            }
        }

        // Seed tickets
        for ($i = 1; $i <= $this->amount; ++$i) {
            try {
                DB::connection('tenant')->beginTransaction();
                $this->generateTicket();
                DB::connection('tenant')->commit();
            } catch (\Exception $e) {
                DB::connection('tenant')->rollBack();
                echo $e->getMessage();
            }
        }
    }

    private function generateTicket() {
        $randomCategory = TicketCategory::factory()->create();
        $fakeSentence = $this->generateFakeSentence();
        $randomCreator = User::inRandomOrder()->first();
        $demoDate = date('Y-m-d', strtotime('-'.mt_rand(0, 365).' days'));
        $ticketUser = (new TicketUserFactory())->create();
        $randomMaintenanceUser = MaintenanceUsers::inRandomOrder()->first();
        $randomUser = User::inRandomOrder()->first();
        $randomPerson = Person::inRandomOrder()->where('is_customer', 1)->first();
        $dueDate = date('Y-m-d', strtotime('+3 days', strtotime($demoDate)));
        $status = rand(0, 1);

        $ticket = (new TicketFactory())->make([
            'creator_type' => 'admin',
            'creator_id' => $randomCreator->id,
            'status' => $status,
            'due_date' => $dueDate,
            'title' => 'Dummy Ticket',
            'content' => $fakeSentence,
            'category_id' => $randomCategory->id,
            'created_at' => $demoDate,
            'updated_at' => $demoDate,
        ]);

        if ($randomCategory->out_source) {
            $ticket->assigned_id = $randomUser->id;
            $ticket->owner_id = $randomMaintenanceUser->id;
            $ticket->owner_type = 'maintenance_user';
            $ticket->save();
            try {
                $amount = random_int(10, 200);
            } catch (\Exception $e) {
                $amount = 50;
            }
            $ticketOutsource = (new TicketOutsourceFactory())->create([
                'ticket_id' => $ticket->id,
                'amount' => $amount,
                'created_at' => $demoDate,
                'updated_at' => $demoDate,
            ]);
            $randomCategory->out_source = $ticketOutsource->id;
            $randomCategory->save();
        } else {
            $ticket->assigned_id = $ticketUser->id;
            $ticket->owner_id = $randomPerson->id;
            $ticket->owner_type = 'person';
            $ticket->save();
        }
    }

    private function generateFakeSentence($minWords = 5, $maxWords = 15) {
        $loremIpsum =
            'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.';
        $words = explode(' ', $loremIpsum);
        $numWords = rand($minWords, $maxWords);

        shuffle($words);
        $fakeSentence = implode(' ', array_slice($words, 0, $numWords));

        // Capitalize the first letter of the sentence.
        $fakeSentence = ucfirst($fakeSentence);

        // Add a period at the end.
        $fakeSentence .= '.';

        return $fakeSentence;
    }
}

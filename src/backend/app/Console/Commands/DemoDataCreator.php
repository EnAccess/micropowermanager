<?php

namespace App\Console\Commands;

use App\Models\MaintenanceUsers;
use App\Models\Person\Person;
use App\Models\User;
use Illuminate\Console\View\Components\Warn;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inensus\Ticket\Models\Ticket;
use Inensus\Ticket\Models\TicketCategory;
use Inensus\Ticket\Models\TicketOutsource;
use Inensus\Ticket\Models\TicketUser;

class DemoDataCreator extends AbstractSharedCommand {
    protected $signature = 'demo:create-data {amount} {--company-id=} {--type=}';
    protected $description = 'Creates transaction and ticket data for Demo Company';

    public function handle() {
        $companyId = $this->option('company-id');
        $type = $this->option('type') ?? 'transaction';
        $amount = $this->argument('amount');

        if (config('app.env') == 'production') {
            echo 'production mode is not allowed to create fake transactions';

            return;
        }

        for ($i = 1; $i <= $amount; ++$i) {
            echo "$type is generating number: $i  \n";
            try {
                DB::connection('shard')->beginTransaction();
                if ($type == 'transaction') {
                    (new Warn($this->getOutput()))->render(
                        'Generating Transaction data using script is no longer supported. Use `artisan db:seed --TransactionSeeder` instead.`'
                    );
                } else {
                    $this->generateTicket();
                }
                DB::connection('shard')->commit();
            } catch (\Exception $e) {
                DB::connection('shard')->rollBack();
                echo $e->getMessage();
                throw $e;
            }
        }
    }

    private function generateTicket() {
        $randomCategory = TicketCategory::query()->inRandomOrder()->first();
        $fakeSentence = $this->generateFakeSentence();
        $randomCreator = User::inRandomOrder()->first();
        $demoDate = date('Y-m-d', strtotime('-'.mt_rand(0, 365).' days'));
        $ticketUser = TicketUser::inRandomOrder()->first();
        $randomMaintenanceUser = MaintenanceUsers::inRandomOrder()->first();
        $randomPerson = Person::inRandomOrder()->where('is_customer', 1)->first();
        $dueDate = date('Y-m-d', strtotime('+3 days', strtotime($demoDate)));
        $status = rand(0, 1);

        $ticket = Ticket::query()->make([
            'ticket_id' => Str::random(10),
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
            $ticket->assigned_id = null;
            $ticket->owner_id = $randomMaintenanceUser->id;
            $ticket->owner_type = 'maintenance_user';
            $ticket->save();
            try {
                $amount = random_int(10, 200);
            } catch (\Exception $e) {
                $amount = 50;
            }
            TicketOutsource::query()->create([
                'ticket_id' => $ticket->id,
                'amount' => $amount,
                'created_at' => $demoDate,
                'updated_at' => $demoDate,
            ]);
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

<?php

namespace App\Console\Commands;

use App\Helpers\TokenGenerator;
use App\Models\MainSettings;
use App\Models\MaintenanceUsers;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterToken;
use App\Models\Person\Person;
use App\Models\Token;
use App\Models\Transaction\AgentTransaction;
use App\Models\Transaction\AirtelTransaction;
use App\Models\Transaction\Transaction;
use App\Models\Transaction\VodacomTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inensus\CalinMeter\Models\CalinTransaction;
use Inensus\SwiftaPaymentProvider\Models\SwiftaTransaction;
use Inensus\Ticket\Models\Ticket;
use Inensus\Ticket\Models\TicketCategory;
use Inensus\Ticket\Models\TicketOutsource;
use Inensus\Ticket\Models\TicketUser;
use Inensus\WavecomPaymentProvider\Models\WaveComTransaction;
use Inensus\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;

class DemoDataCreator extends AbstractSharedCommand {
    protected $signature = 'demo:create-data {amount} {--company-id=} {--type=}';
    protected $description = 'Creates transaction and ticket data for Demo Company';

    private $transactionTypes = [
        SwiftaTransaction::class,
        WaveComTransaction::class,
        WaveMoneyTransaction::class,
        AgentTransaction::class,
        VodacomTransaction::class,
        AirtelTransaction::class,
    ];

    public function __construct(
        private Transaction $transaction,
        private AgentTransaction $agentTransaction,
        private WaveMoneyTransaction $waveMoneyTransaction,
        private SwiftaTransaction $swiftaTransaction,
        private WaveComTransaction $waveComTransaction,
        private VodacomTransaction $vodacomTransaction,
        private AirtelTransaction $airtelTransaction,
        private Meter $meter,
        private Token $token,
        private MeterToken $meterToken,
        private CalinTransaction $calinTransaction,
        private MainSettings $mainSettings,
        private TicketCategory $ticketCategory,
        private User $user,
        private TicketUser $ticketUser,
        private Person $person,
        private Ticket $ticket,
        private MaintenanceUsers $maintenanceUsers,
        private TicketOutsource $ticketOutsource,
    ) {
        parent::__construct();
    }

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
                    $this->generateTransaction();
                } else {
                    $this->generateTicket();
                }
                DB::connection('shard')->commit();
            } catch (\Exception $e) {
                DB::connection('shard')->rollBack();
                echo $e->getMessage();
            }
        }
    }

    private function generateTransaction(): void {
        try {
            // get randomly a user
            $randomMeter = $this->meter::inRandomOrder()->with([
                'device',
                'tariff',
            ])->limit(1)->firstOrFail();
        } catch (ModelNotFoundException $x) {
            echo 'failed to find a random meter';

            return;
        } catch (\Exception $x) {
            echo 'boom';

            return;
        }

        $demoDate = date('Y-m-d', strtotime('-'.mt_rand(0, 365).' days'));

        try {
            $meterOwnerPhoneNumber = $randomMeter->device->person->addresses()->firstOrFail();
        } catch (\Exception $x) {
            echo 'failed to get meter owner address';

            return;
        }

        try {
            $amount = random_int(1000, 20000);
        } catch (\Exception $e) {
            $amount = 300;
        }

        $randomTransactionType = $this->getTransactionTypeRandomlyFromTransactionTypes();
        $transactionType = app()->make($randomTransactionType);

        $transaction = $this->transaction->newQuery()->make([
            'amount' => $amount,
            'type' => 'energy',
            'message' => $randomMeter['serial_number'],
            'sender' => $meterOwnerPhoneNumber['phone'],
            'created_at' => $demoDate,
            'updated_at' => $demoDate,
        ]);
        $subTransaction = null;

        $manufacturerTransaction = $this->calinTransaction->newQuery()->create([]);

        if ($transactionType instanceof AgentTransaction) {
            $city = $randomMeter->device->person->addresses()->first()->city()->first();
            $miniGrid = $city->miniGrid()->first();
            $agent = $miniGrid->agent()->first();
            $subTransaction = $this->agentTransaction->newQuery()->create([
                'agent_id' => $agent->id,
                'device_id' => 'test-device',
                'status' => 1,
                'manufacturer_transaction_id' => $manufacturerTransaction->id,
                'manufacturer_transaction_type' => 'calin_transaction',
                'created_at' => $demoDate,
                'updated_at' => $demoDate,
            ]);
        }

        if ($transactionType instanceof SwiftaTransaction) {
            $subTransaction = $this->swiftaTransaction->newQuery()->create([
                'transaction_reference' => Str::random(10),
                'status' => 1,
                'amount' => $amount,
                'cipher' => Str::random(10),
                'timestamp' => strval(time()),
                'manufacturer_transaction_id' => $manufacturerTransaction->id,
                'manufacturer_transaction_type' => 'calin_transaction',
                'created_at' => $demoDate,
                'updated_at' => $demoDate,
            ]);
        }

        if ($transactionType instanceof WaveMoneyTransaction) {
            $mainSettings = $this->mainSettings->newQuery()->first();
            $subTransaction = $this->waveMoneyTransaction->newQuery()->create([
                'transaction_reference' => Str::random(10),
                'status' => 1,
                'amount' => $amount,
                'order_id' => Str::random(10),
                'reference_id' => Str::random(10),
                'currency' => $mainSettings ? $mainSettings->currency : '$',
                'customer_id' => $randomMeter->device->person->id,
                'meter_serial' => $randomMeter['serial_number'],
                'external_transaction_id' => Str::random(10),
                'attempts' => 1,
                'manufacturer_transaction_id' => $manufacturerTransaction->id,
                'manufacturer_transaction_type' => 'calin_transaction',
                'created_at' => $demoDate,
                'updated_at' => $demoDate,
            ]);
        }

        if ($transactionType instanceof WaveComTransaction) {
            $subTransaction = $this->waveComTransaction->newQuery()->create([
                'transaction_id' => Str::random(10),
                'sender' => $meterOwnerPhoneNumber['phone'],
                'message' => $randomMeter['serial_number'],
                'status' => 1,
                'amount' => $amount,
                'manufacturer_transaction_id' => $manufacturerTransaction->id,
                'manufacturer_transaction_type' => 'calin_transaction',
                'created_at' => $demoDate,
                'updated_at' => $demoDate,
            ]);
        }

        if ($transactionType instanceof VodacomTransaction) {
            $subTransaction = $this->vodacomTransaction->newQuery()->create([
                'conversation_id' => Str::random(20),
                'originator_conversation_id' => Str::random(20),
                'mpesa_receipt' => Str::random(10),
                'transaction_id' => Str::random(10),
                'status' => 1,
                'manufacturer_transaction_id' => $manufacturerTransaction->id,
                'manufacturer_transaction_type' => 'calin_transaction',
                'created_at' => $demoDate,
                'updated_at' => $demoDate,
            ]);
        }

        if ($transactionType instanceof AirtelTransaction) {
            $subTransaction = $this->airtelTransaction->newQuery()->create([
                'interface_id' => Str::random(20),
                'business_number' => Str::random(20),
                'trans_id' => Str::random(10),
                'tr_id' => Str::random(10),
                'status' => 1,
                'manufacturer_transaction_id' => $manufacturerTransaction->id,
                'manufacturer_transaction_type' => 'calin_transaction',
                'created_at' => $demoDate,
                'updated_at' => $demoDate,
            ]);
        }

        $transaction->originalTransaction()->associate($subTransaction);
        $transaction->save();

        try {
            // create an object for the token job
            $transactionData = \App\Misc\TransactionDataContainer::initialize($transaction);
        } catch (\Exception $exception) {
            event('transaction.failed', [$this->transaction, $exception->getMessage()]);
            throw $exception;
        }

        // pay access rate
        $accessRatePayer = resolve('AccessRatePayer');
        $accessRatePayer->initialize($transactionData);
        $transactionData = $accessRatePayer->pay();

        // pay appliance installments
        $applianceInstallmentPayer = resolve('ApplianceInstallmentPayer');
        $applianceInstallmentPayer->initialize($transactionData);
        $transactionData->transaction->amount = $applianceInstallmentPayer->payInstallments();
        $transactionData->totalAmount = $transactionData->transaction->amount;
        $transactionData->paidRates = $applianceInstallmentPayer->paidRates;
        $transactionData->shsLoan = $applianceInstallmentPayer->shsLoan;

        // generate random token
        if ($transactionData->transaction->amount > 0) {
            $tokenData = [
                'token' => TokenGenerator::generate(),
                'load' => round(
                    $transactionData->transaction->amount /
                        $randomMeter['tariff']['price'],
                    2
                ),
            ];
            $token = $this->token->newQuery()->make(['token' => $tokenData['token'], 'load' => $tokenData['load']]);
            $token->transaction()->associate($transaction);
            $token->save();
            $transactionData->token = $token;

            // generate meter_token
            $meterTokenData = [
                'meter_id' => $randomMeter->id,
                'token' => TokenGenerator::generate(),
                'energy' => round(
                    $transactionData->transaction->amount /
                    $randomMeter['tariff']['price'],
                    2
                ),
                'transaction_id' => $transaction->id,
            ];
            $meterToken = $this->meterToken->newQuery()->make(['meter_id' => $meterTokenData['meter_id'],
                'token' => $meterTokenData['token'], '' => $meterTokenData['energy'],
                'transaction_id' => $meterTokenData['transaction_id']]);
            $meterToken->save();

            // payment event
            event(
                'payment.successful',
                [
                    'amount' => $transactionData->transaction->amount,
                    'paymentService' => $transactionData->transaction->original_transaction_type,
                    'paymentType' => 'energy',
                    'sender' => $transactionData->transaction->sender,
                    'paidFor' => $token,
                    'payer' => $transactionData->device->person,
                    'transaction' => $transactionData->transaction,
                ]
            );

            // TODO: This currently doesn't work, it throws error that SMS is not configured.
            // event('transaction.successful', [$transactionData->transaction]);
        }
    }

    private function getTransactionTypeRandomlyFromTransactionTypes() {
        return $this->transactionTypes[array_rand($this->transactionTypes)];
    }

    private function generateTicket() {
        $randomCategory = $this->ticketCategory->newQuery()->inRandomOrder()->first();
        $fakeSentence = $this->generateFakeSentence();
        $randomCreator = $this->user->inRandomOrder()->first();
        $demoDate = date('Y-m-d', strtotime('-'.mt_rand(0, 365).' days'));
        $ticketUser = $this->ticketUser->inRandomOrder()->first();
        $randomMaintenanceUser = $this->maintenanceUsers->inRandomOrder()->first();
        $randomPerson = $this->person->inRandomOrder()->where('is_customer', 1)->first();
        $dueDate = date('Y-m-d', strtotime('+3 days', strtotime($demoDate)));
        $status = rand(0, 1);

        $ticket = $this->ticket->newQuery()->make([
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
            $this->ticketOutsource->newQuery()->create([
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

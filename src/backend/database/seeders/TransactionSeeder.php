<?php

namespace Database\Seeders;

use App\Models\MainSettings;
use App\Models\Meter\Meter;
use App\Models\Transaction\AgentTransaction;
use App\Models\Transaction\AirtelTransaction;
use App\Models\Transaction\VodacomTransaction;
use Database\Factories\AgentTransactionFactory;
use Database\Factories\MeterTokenFactory;
use Database\Factories\TokenFactory;
use Database\Factories\TransactionFactory;
use Illuminate\Console\View\Components\Info;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inensus\CalinMeter\Models\CalinTransaction;
use Inensus\SwiftaPaymentProvider\Models\SwiftaTransaction;
use Inensus\WavecomPaymentProvider\Models\WaveComTransaction;
use Inensus\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;
use MPM\DatabaseProxy\DatabaseProxyManagerService;

class TransactionSeeder extends Seeder {
    public function __construct(
        private DatabaseProxyManagerService $databaseProxyManagerService,
    ) {
        $this->databaseProxyManagerService->buildDatabaseConnectionDemoCompany();
    }

    private $transactionTypes = [
        SwiftaTransaction::class,
        WaveComTransaction::class,
        WaveMoneyTransaction::class,
        AgentTransaction::class,
        VodacomTransaction::class,
        AirtelTransaction::class,
    ];

    private $amount = 1000;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        (new Info($this->command->getOutput()))->render(
            "Running TransactionSeeder to generate $this->amount transactions. This may take some time."
        );

        for ($i = 1; $i <= $this->amount; ++$i) {
            try {
                DB::connection('shard')->beginTransaction();
                $this->generateTransaction();
                DB::connection('shard')->commit();
            } catch (\Exception $e) {
                DB::connection('shard')->rollBack();
                echo $e->getMessage();
            }
        }
    }

    private function getTransactionTypeRandomlyFromTransactionTypes() {
        return $this->transactionTypes[array_rand($this->transactionTypes)];
    }

    private function generateTransaction(): void {
        try {
            // get randomly a user
            $randomMeter = Meter::inRandomOrder()->with([
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

        $transaction = (new TransactionFactory())->make([
            'amount' => $amount,
            'type' => 'energy',
            'message' => $randomMeter['serial_number'],
            'sender' => $meterOwnerPhoneNumber['phone'],
            'created_at' => $demoDate,
            'updated_at' => $demoDate,
        ]);
        $subTransaction = null;

        // FIXME: What is this?
        $manufacturerTransaction = CalinTransaction::query()->create([]);

        if ($transactionType instanceof AgentTransaction) {
            $city = $randomMeter->device->person->addresses()->first()->city()->first();
            $miniGrid = $city->miniGrid()->first();
            $agent = $miniGrid->agent()->first();
            $subTransaction = (new AgentTransactionFactory())->create([
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
            $subTransaction = SwiftaTransaction::query()->create([
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
            $mainSettings = MainSettings::query()->first();
            $subTransaction = WaveMoneyTransaction::query()->create([
                'status' => 1,
                'amount' => $amount,
                'order_id' => Str::random(10),
                'reference_id' => Str::random(10),
                'currency' => $mainSettings ? $mainSettings->currency : '$',
                'customer_id' => $randomMeter->device->person->id,
                'meter_serial' => $randomMeter['serial_number'],
                'external_transaction_id' => Str::random(10),
                'attempts' => 1,
                'created_at' => $demoDate,
                'updated_at' => $demoDate,
                'manufacturer_transaction_id' => $manufacturerTransaction->id,
                'manufacturer_transaction_type' => 'calin_transaction',
            ]);
        }

        if ($transactionType instanceof WaveComTransaction) {
            $subTransaction = WaveComTransaction::query()->create([
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
            $subTransaction = VodacomTransaction::query()->create([
                'conversation_id' => Str::random(20),
                'originator_conversation_id' => Str::random(20),
                'mpesa_receipt' => Str::random(10),
                'transaction_date' => $demoDate,
                'transaction_id' => Str::random(10),
                'status' => 1,
                'manufacturer_transaction_id' => $manufacturerTransaction->id,
                'manufacturer_transaction_type' => 'calin_transaction',
                'created_at' => $demoDate,
                'updated_at' => $demoDate,
            ]);
        }

        if ($transactionType instanceof AirtelTransaction) {
            $subTransaction = AirtelTransaction::query()->create([
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
            event('transaction.failed', [$transaction, $exception->getMessage()]);
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
                'token' => TokenFactory::generateToken(),
                'load' => round(
                    $transactionData->transaction->amount /
                        $randomMeter['tariff']['price'],
                    2
                ),
            ];
            $token = (new TokenFactory())->make([
                'token' => $tokenData['token'],
                'load' => $tokenData['load'],
            ]);
            $token->transaction()->associate($transaction);
            $token->save();
            $transactionData->token = $token;

            // generate meter_token
            $meterTokenData = [
                'meter_id' => $randomMeter->id,
                'token' => TokenFactory::generateToken(),
                'energy' => round(
                    $transactionData->transaction->amount /
                    $randomMeter['tariff']['price'],
                    2
                ),
                'transaction_id' => $transaction->id,
            ];
            $meterToken = (new MeterTokenFactory())->make([
                'meter_id' => $meterTokenData['meter_id'],
                'token' => $meterTokenData['token'],
                'energy' => $meterTokenData['energy'],
                'transaction_id' => $meterTokenData['transaction_id'],
            ]);
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
}

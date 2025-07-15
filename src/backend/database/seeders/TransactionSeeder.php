<?php

namespace Database\Seeders;

use App\Events\PaymentSuccessEvent;
use App\Events\TransactionFailedEvent;
use App\Models\Device;
use App\Models\MainSettings;
use App\Models\Meter\Meter;
use App\Models\SolarHomeSystem;
use App\Models\Token;
use App\Models\Transaction\AgentTransaction;
use Database\Factories\AgentTransactionFactory;
use Database\Factories\TokenFactory;
use Database\Factories\TransactionFactory;
use Illuminate\Console\View\Components\Info;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inensus\AngazaSHS\Models\AngazaTransaction;
use Inensus\CalinMeter\Models\CalinTransaction;
use Inensus\SunKingSHS\Models\SunKingTransaction;
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
                DB::connection('tenant')->beginTransaction();
                $this->generateTransaction();
                DB::connection('tenant')->commit();
            } catch (\Exception $e) {
                DB::connection('tenant')->rollBack();
                echo $e->getMessage();
            }
        }
    }

    private function getTransactionTypeRandomlyFromTransactionTypes() {
        return $this->transactionTypes[array_rand($this->transactionTypes)];
    }

    private function getManufacturerTransactionFromDeviceType($deviceModel): CalinTransaction|AngazaTransaction|SunKingTransaction {
        if ($deviceModel instanceof Meter) {
            return CalinTransaction::create();
        }

        if ($deviceModel instanceof SolarHomeSystem) {
            $transactionClass = collect([
                AngazaTransaction::class,
                SunKingTransaction::class,
            ])->random();

            return $transactionClass::create();
        }

        throw new \Exception('Unsupported device type for transaction');
    }

    private function generateTransaction(): void {
        try {
            // Get a random device (either Meter or SHS) that has a person with addresses
            $randomDevice = Device::inRandomOrder()
                ->whereHasMorph('device', [Meter::class, SolarHomeSystem::class])
                ->whereHas('person', function ($query) {
                    $query->whereHas('addresses');
                })
                ->with(['device', 'person.addresses'])
                ->firstOrFail();

            // Get the associated model (Meter or SolarHomeSystem)
            $deviceModel = $randomDevice->device;
        } catch (ModelNotFoundException $x) {
            echo 'failed to find a random device with person and addresses';

            return;
        } catch (\Exception $x) {
            echo 'boom';

            return;
        }

        $demoDate = date('Y-m-d', strtotime('-'.mt_rand(0, 365).' days'));

        try {
            $deviceOwnerPhoneNumber = $randomDevice->person->addresses()->firstOrFail();
        } catch (\Exception $x) {
            echo 'failed to get device owner address';

            return;
        }

        try {
            $amount = random_int(1000, 20000);
        } catch (\Exception $e) {
            $amount = 300;
        }

        $randomTransactionType = $this->getTransactionTypeRandomlyFromTransactionTypes();
        $transactionType = app()->make($randomTransactionType);

        // Get device serial based on device type
        $deviceSerial = $randomDevice->device_serial;

        if ($transactionType instanceof AgentTransaction) {
            $city = $randomDevice->person->addresses()->first()->city()->first();
            $miniGrid = $city->miniGrid()->first();
            // get a random agent from the mini grid
            $agent = $miniGrid->agents()->inRandomOrder()->first();
            $transaction = (new TransactionFactory())->make([
                'amount' => $amount,
                'type' => 'energy',
                'message' => $deviceSerial,
                'sender' => 'Agent-'.$agent->id,
                'created_at' => $demoDate,
                'updated_at' => $demoDate,
            ]);
        } else {
            $transaction = (new TransactionFactory())->make([
                'amount' => $amount,
                'type' => 'energy',
                'message' => $deviceSerial,
                'sender' => $deviceOwnerPhoneNumber['phone'],
                'created_at' => $demoDate,
                'updated_at' => $demoDate,
            ]);
        }

        $subTransaction = null;

        // FIXME: What is this?
        $manufacturerTransaction = $this->getManufacturerTransactionFromDeviceType($deviceModel);

        if ($transactionType instanceof AgentTransaction) {
            $city = $randomDevice->person->addresses()->first()->city()->first();
            $miniGrid = $city->miniGrid()->first();
            $agent = $miniGrid->agents()->inRandomOrder()->first();
            if (!$agent) {
                return;
            }
            $subTransaction = (new AgentTransactionFactory())->create([
                'agent_id' => $agent->id,
                'mobile_device_id' => 'test-device',
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
                'customer_id' => $randomDevice->person->id,
                'meter_serial' => $deviceSerial,
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
                'sender' => $deviceOwnerPhoneNumber['phone'],
                'message' => $deviceSerial,
                'status' => 1,
                'amount' => $amount,
                'manufacturer_transaction_id' => $manufacturerTransaction->id,
                'manufacturer_transaction_type' => 'calin_transaction',
                'created_at' => $demoDate,
                'updated_at' => $demoDate,
            ]);
        }

        if ($transactionType instanceof SunKingTransaction) {
            $subTransaction = SunKingTransaction::query()->create([
                'created_at' => $demoDate,
                'updated_at' => $demoDate,
                'status' => 1,
                'amount' => $amount,
            ]);
        }

        if ($transactionType instanceof AngazaTransaction) {
            $subTransaction = AngazaTransaction::query()->create([
                'created_at' => $demoDate,
                'updated_at' => $demoDate,
                'status' => 1,
                'amount' => $amount,
            ]);
        }

        $transaction->originalTransaction()->associate($subTransaction);
        $transaction->save();

        try {
            // create an object for the token job
            $transactionData = \App\Misc\TransactionDataContainer::initialize($transaction);
        } catch (\Exception $exception) {
            event(new TransactionFailedEvent($transaction, $exception->getMessage()));
            throw $exception;
        }

        // only process access rate for meter devices
        if ($deviceModel instanceof Meter) {
            // pay access rate
            $accessRatePayer = resolve('AccessRatePayer');
            $accessRatePayer->initialize($transactionData);
            $transactionData = $accessRatePayer->pay();
        }

        // pay appliance installments
        $applianceInstallmentPayer = resolve('ApplianceInstallmentPayer');
        $applianceInstallmentPayer->initialize($transactionData);
        $transactionData->transaction->amount = $applianceInstallmentPayer->payInstallments();
        $transactionData->totalAmount = $transactionData->transaction->amount;
        $transactionData->paidRates = $applianceInstallmentPayer->paidRates;
        $transactionData->shsLoan = $applianceInstallmentPayer->shsLoan;

        // generate random token
        if ($transactionData->transaction->amount > 0) {
            // Check if this is an Meter device
            if ($deviceModel instanceof Meter) {
                $tokenType = Token::TYPE_ENERGY;
                $tokenUnit = Token::UNIT_KWH;
            } else {
                $tokenType = Token::TYPE_TIME;
                $tokenUnit = Token::UNIT_DAYS;
            }

            // Create device token
            $tokenData = [
                'token' => TokenFactory::generateToken(),
                'token_type' => $tokenType,
                'token_unit' => $tokenUnit,
                'token_amount' => round(
                    $transactionData->transaction->amount /
                        ($deviceModel instanceof Meter ? $deviceModel->tariff->price : 1),
                    2
                ),
            ];
            $token = (new TokenFactory())->make([
                'token' => $tokenData['token'],
                'token_type' => $tokenData['token_type'],
                'token_unit' => $tokenData['token_unit'],
                'token_amount' => $tokenData['token_amount'],
                'device_id' => $randomDevice->id,
            ]);
            $token->transaction()->associate($transaction);
            $token->save();
            $transactionData->token = $token;

            event(new PaymentSuccessEvent(
                amount: $transactionData->transaction->amount,
                paymentService: $transactionData->transaction->original_transaction_type,
                paymentType: 'energy',
                sender: $transactionData->transaction->sender,
                paidFor: $token,
                payer: $transactionData->device->person,
                transaction: $transactionData->transaction,
            ));
        }
    }
}

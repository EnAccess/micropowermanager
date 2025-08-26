<?php

namespace Database\Seeders;

use App\Events\PaymentSuccessEvent;
use App\Models\Asset;
use App\Models\AssetPerson;
use App\Models\AssetRate;
use App\Models\AssetType;
use App\Models\Device;
use App\Models\Person\Person;
use App\Models\Transaction\CashTransaction;
use App\Models\Transaction\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use MPM\DatabaseProxy\DatabaseProxyManagerService;

class OutstandingDebtsSeeder extends Seeder {
    public function __construct(
        private DatabaseProxyManagerService $databaseProxyManagerService,
    ) {
        $this->databaseProxyManagerService->buildDatabaseConnectionDemoCompany();
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        $this->command->info('Creating demo data for OutstandingDebts feature...');

        // Get existing customers and assets
        $customers = Person::where('is_customer', true)->get();
        $assetType = AssetType::where('name', 'Solar Home System')->first();
        $assets = Asset::where('asset_type_id', $assetType->id)->get();

        if ($customers->isEmpty() || $assets->isEmpty()) {
            $this->command->warn('No customers or assets found. Skipping OutstandingDebts seeder.');

            return;
        }

        // Create AssetPerson records with outstanding debts
        $this->createAssetPersonWithOutstandingDebts($assets);

        // Generate payment transactions to simulate ApplianceInstallmentPayer
        $this->generatePaymentTransactions();

        $this->command->info('OutstandingDebts demo data created successfully!');
    }

    /**
     * @param Collection<int, Asset> $assets
     */
    private function createAssetPersonWithOutstandingDebts(Collection $assets): void {
        // Get existing devices (meters and SHS) that have customers
        $devices = Device::whereHasMorph('device', [Meter::class, SolarHomeSystem::class])
            ->whereHas('person', function ($query) {
                $query->where('is_customer', true);
            })
            ->with(['device', 'person'])
            ->get();

        if ($devices->isEmpty()) {
            $this->command->warn('No devices found with customers. Skipping OutstandingDebts seeder.');

            return;
        }

        // Create 15-25 AssetPerson records with outstanding debts
        $count = rand(15, 25);

        for ($i = 0; $i < $count; ++$i) {
            $device = $devices->random();
            $customer = $device->person;
            /** @var Asset $asset */
            $asset = $assets->random();

            // Create different scenarios for demo
            $scenario = rand(1, 4);

            switch ($scenario) {
                case 1:
                    // Scenario 1: Recent purchase with some overdue payments
                    $this->createRecentPurchaseScenario($customer, $asset, $device);
                    break;
                case 2:
                    // Scenario 2: Long-term debt with multiple overdue payments
                    $this->createLongTermDebtScenario($customer, $asset, $device);
                    break;
                case 3:
                    // Scenario 3: Partial payments with remaining balance
                    $this->createPartialPaymentScenario($customer, $asset, $device);
                    break;
                case 4:
                    // Scenario 4: Almost paid off but with overdue final payments
                    $this->createAlmostPaidScenario($customer, $asset, $device);
                    break;
            }
        }
    }

    private function createRecentPurchaseScenario(Person $customer, Asset $asset, Device $device): void {
        $assetPerson = AssetPerson::create([
            'asset_id' => $asset->id,
            'person_id' => $customer->id,
            'device_serial' => $device->device_serial,
            'total_cost' => $asset->price,
            'rate_count' => 12,
            'down_payment' => 0,
            'first_payment_date' => Carbon::now()->subMonths(3),
            'creator_type' => 'user',
            'creator_id' => 1,
        ]);

        $this->createAssetRatesWithRecentOverdue($assetPerson);
    }

    private function createLongTermDebtScenario(Person $customer, Asset $asset, Device $device): void {
        $assetPerson = AssetPerson::create([
            'asset_id' => $asset->id,
            'person_id' => $customer->id,
            'device_serial' => $device->device_serial,
            'total_cost' => $asset->price,
            'rate_count' => 24,
            'down_payment' => 0,
            'first_payment_date' => Carbon::now()->subMonths(18),
            'creator_type' => 'user',
            'creator_id' => 1,
        ]);

        $this->createAssetRatesWithLongTermDebt($assetPerson);
    }

    private function createPartialPaymentScenario(Person $customer, Asset $asset, Device $device): void {
        $assetPerson = AssetPerson::create([
            'asset_id' => $asset->id,
            'person_id' => $customer->id,
            'device_serial' => $device->device_serial,
            'total_cost' => $asset->price,
            'rate_count' => 12,
            'down_payment' => 0,
            'first_payment_date' => Carbon::now()->subMonths(8),
            'creator_type' => 'user',
            'creator_id' => 1,
        ]);

        $this->createAssetRatesWithPartialPayments($assetPerson);
    }

    private function createAlmostPaidScenario(Person $customer, Asset $asset, Device $device): void {
        $assetPerson = AssetPerson::create([
            'asset_id' => $asset->id,
            'person_id' => $customer->id,
            'device_serial' => $device->device_serial,
            'total_cost' => $asset->price,
            'rate_count' => 12,
            'down_payment' => 0,
            'first_payment_date' => Carbon::now()->subMonths(10),
            'creator_type' => 'user',
            'creator_id' => 1,
        ]);

        $this->createAssetRatesAlmostPaid($assetPerson);
    }

    private function createAssetRatesWithRecentOverdue(AssetPerson $assetPerson): void {
        $monthlyRate = floor($assetPerson->total_cost / $assetPerson->rate_count);
        $remainingAmount = $assetPerson->total_cost;

        for ($month = 1; $month <= $assetPerson->rate_count; ++$month) {
            if ($month === $assetPerson->rate_count) {
                $rateAmount = $remainingAmount;
            } else {
                $rateAmount = $monthlyRate;
                $remainingAmount -= $monthlyRate;
            }

            $dueDate = Carbon::parse($assetPerson->first_payment_date)->addMonths($month);
            $isOverdue = $dueDate->isPast();

            // Recent purchase: only last 1-2 payments are overdue
            $shouldBeOverdue = $isOverdue && $month >= ($assetPerson->rate_count - 2);
            $remaining = $shouldBeOverdue ? $rateAmount : 0;

            AssetRate::create([
                'asset_person_id' => $assetPerson->id,
                'rate_cost' => $rateAmount,
                'remaining' => $remaining,
                'due_date' => $dueDate->format('Y-m-d'),
                'remind' => $shouldBeOverdue ? rand(1, 2) : 0,
            ]);
        }
    }

    private function createAssetRatesWithLongTermDebt(AssetPerson $assetPerson): void {
        $monthlyRate = floor($assetPerson->total_cost / $assetPerson->rate_count);
        $remainingAmount = $assetPerson->total_cost;

        for ($month = 1; $month <= $assetPerson->rate_count; ++$month) {
            if ($month === $assetPerson->rate_count) {
                $rateAmount = $remainingAmount;
            } else {
                $rateAmount = $monthlyRate;
                $remainingAmount -= $monthlyRate;
            }

            $dueDate = Carbon::parse($assetPerson->first_payment_date)->addMonths($month);
            $isOverdue = $dueDate->isPast();

            // Long-term debt: multiple overdue payments with high reminder counts
            $shouldBeOverdue = $isOverdue && rand(0, 1) === 1; // 50% chance of being overdue
            $remaining = $shouldBeOverdue ? $rateAmount : 0;

            AssetRate::create([
                'asset_person_id' => $assetPerson->id,
                'rate_cost' => $rateAmount,
                'remaining' => $remaining,
                'due_date' => $dueDate->format('Y-m-d'),
                'remind' => $shouldBeOverdue ? rand(2, 5) : 0,
            ]);
        }
    }

    private function createAssetRatesWithPartialPayments(AssetPerson $assetPerson): void {
        $monthlyRate = floor($assetPerson->total_cost / $assetPerson->rate_count);
        $remainingAmount = $assetPerson->total_cost;

        for ($month = 1; $month <= $assetPerson->rate_count; ++$month) {
            if ($month === $assetPerson->rate_count) {
                $rateAmount = $remainingAmount;
            } else {
                $rateAmount = $monthlyRate;
                $remainingAmount -= $monthlyRate;
            }

            $dueDate = Carbon::parse($assetPerson->first_payment_date)->addMonths($month);
            $isOverdue = $dueDate->isPast();

            // Partial payments: some overdue rates have partial payments
            $shouldBeOverdue = $isOverdue && rand(0, 1) === 1;
            $hasPartialPayment = $shouldBeOverdue && rand(0, 1) === 1;
            $remaining = $shouldBeOverdue ? ($hasPartialPayment ? rand(1, $rateAmount - 1) : $rateAmount) : 0;

            AssetRate::create([
                'asset_person_id' => $assetPerson->id,
                'rate_cost' => $rateAmount,
                'remaining' => $remaining,
                'due_date' => $dueDate->format('Y-m-d'),
                'remind' => $shouldBeOverdue ? rand(1, 3) : 0,
            ]);
        }
    }

    private function createAssetRatesAlmostPaid(AssetPerson $assetPerson): void {
        $monthlyRate = floor($assetPerson->total_cost / $assetPerson->rate_count);
        $remainingAmount = $assetPerson->total_cost;

        for ($month = 1; $month <= $assetPerson->rate_count; ++$month) {
            if ($month === $assetPerson->rate_count) {
                $rateAmount = $remainingAmount;
            } else {
                $rateAmount = $monthlyRate;
                $remainingAmount -= $monthlyRate;
            }

            $dueDate = Carbon::parse($assetPerson->first_payment_date)->addMonths($month);
            $isOverdue = $dueDate->isPast();

            // Almost paid: only last 1-2 payments are overdue
            $shouldBeOverdue = $isOverdue && $month >= ($assetPerson->rate_count - 1);
            $remaining = $shouldBeOverdue ? $rateAmount : 0;

            AssetRate::create([
                'asset_person_id' => $assetPerson->id,
                'rate_cost' => $rateAmount,
                'remaining' => $remaining,
                'due_date' => $dueDate->format('Y-m-d'),
                'remind' => $shouldBeOverdue ? rand(1, 2) : 0,
            ]);
        }
    }

    /**
     * Generate payment transactions to simulate ApplianceInstallmentPayer functionality.
     */
    private function generatePaymentTransactions(): void {
        $this->command->info('Generating payment transactions for outstanding debts...');

        // Get all AssetPerson records with outstanding debts
        $assetPersons = AssetPerson::whereHas('rates', function ($query) {
            $query->where('remaining', '>', 0);
        })->with(['rates', 'person'])->get();

        if ($assetPersons->isEmpty()) {
            $this->command->warn('No AssetPerson records with outstanding debts found. Skipping payment transaction generation.');

            return;
        }

        $this->command->info("Found {$assetPersons->count()} AssetPerson records with outstanding debts.");

        $demoUser = User::first();

        $transactionCount = 0;
        $maxTransactions = min(50, $assetPersons->count() * 2);

        $this->command->info("Will generate up to {$maxTransactions} payment transactions.");

        foreach ($assetPersons as $assetPerson) {
            if ($transactionCount >= $maxTransactions) {
                break;
            }

            $outstandingRates = $assetPerson->rates()->where('remaining', '>', 0)->get();
            $this->command->line("AssetPerson {$assetPerson->id} has {$outstandingRates->count()} outstanding rates.");

            foreach ($outstandingRates as $rate) {
                if ($transactionCount >= $maxTransactions) {
                    break;
                }

                // Create partial payment transactions (simulating customer payments)
                $this->createPartialPaymentTransaction($assetPerson, $rate, $demoUser);
                ++$transactionCount;

                // Sometimes create full payment transactions
                if (rand(0, 3) === 0) {
                    $this->createFullPaymentTransaction($assetPerson, $rate, $demoUser);
                    ++$transactionCount;
                }
            }
        }

        // Create historical payment transactions to simulate payment history
        $this->createHistoricalPaymentTransactions($assetPersons, $demoUser);

        $this->command->info("Generated {$transactionCount} payment transactions for outstanding debts.");
    }

    /**
     * Create historical payment transactions to simulate customer payment history.
     *
     * @param Collection<int, AssetPerson> $assetPersons
     * @param User                         $demoUser
     */
    private function createHistoricalPaymentTransactions(Collection $assetPersons, User $demoUser): void {
        $historicalTransactionCount = 0;
        $maxHistoricalTransactions = 30;

        foreach ($assetPersons as $assetPerson) {
            if ($historicalTransactionCount >= $maxHistoricalTransactions) {
                break;
            }

            // Create 1-3 historical payments per asset person
            $historicalPayments = rand(1, 3);

            for ($i = 0; $i < $historicalPayments; ++$i) {
                if ($historicalTransactionCount >= $maxHistoricalTransactions) {
                    break;
                }

                $this->createHistoricalPaymentTransaction($assetPerson, $demoUser, $i);
                ++$historicalTransactionCount;
            }
        }

        $this->command->info("Created {$historicalTransactionCount} historical payment transactions.");
    }

    /**
     * Create a historical payment transaction.
     */
    private function createHistoricalPaymentTransaction(AssetPerson $assetPerson, User $demoUser, int $paymentIndex): void {
        try {
            // Calculate a historical date (1-12 months ago)
            $monthsAgo = rand(1, 12);
            $historicalDate = Carbon::now()->subMonths($monthsAgo);

            // Get sender information
            $sender = $assetPerson->person->phone ?? $assetPerson->person->email ?? 'Customer-'.$assetPerson->person->id;

            // Create a random payment amount (simulating what was paid historically)
            $paymentAmount = rand(5000, 25000); // Random amount between 5-25 units

            // Create cash transaction
            $cashTransaction = CashTransaction::create([
                'user_id' => $demoUser->id,
                'status' => 1, // Success
                'manufacturer_transaction_id' => null,
                'manufacturer_transaction_type' => null,
                'created_at' => $historicalDate,
                'updated_at' => $historicalDate,
            ]);

            // Create main transaction using proper polymorphic relationship
            $transaction = new Transaction([
                'amount' => $paymentAmount,
                'type' => 'deferred_payment',
                'sender' => $sender,
                'message' => $assetPerson->device_serial,
                'created_at' => $historicalDate,
                'updated_at' => $historicalDate,
            ]);

            // Associate the cash transaction using Laravel's polymorphic relationship
            $transaction->originalTransaction()->associate($cashTransaction);
            $transaction->save();

            // Find a rate that would have been due around this time
            $dueDate = Carbon::parse($assetPerson->first_payment_date)->addMonths($paymentIndex + 1);

            // Create a historical AssetRate record if it doesn't exist
            $historicalRate = AssetRate::where('asset_person_id', $assetPerson->id)
                ->where('due_date', $dueDate->format('Y-m-d'))
                ->first();

            if (!$historicalRate) {
                // Create a historical rate record
                $monthlyRate = floor($assetPerson->total_cost / $assetPerson->rate_count);
                $historicalRate = AssetRate::create([
                    'asset_person_id' => $assetPerson->id,
                    'rate_cost' => $monthlyRate,
                    'remaining' => 0, // This was paid historically
                    'due_date' => $dueDate->format('Y-m-d'),
                    'remind' => 0,
                    'created_at' => $historicalDate,
                    'updated_at' => $historicalDate,
                ]);
            }

            // Dispatch PaymentSuccessEvent to create payment history
            event(new PaymentSuccessEvent(
                amount: $paymentAmount,
                paymentService: 'cash_transaction',
                paymentType: 'installment',
                sender: $sender,
                paidFor: $historicalRate,
                payer: $assetPerson->person,
                transaction: $transaction,
            ));
        } catch (\Exception $e) {
            $this->command->warn('Failed to create historical payment transaction: '.$e->getMessage());
        }
    }

    /**
     * Create a partial payment transaction for an overdue rate.
     */
    private function createPartialPaymentTransaction(AssetPerson $assetPerson, AssetRate $rate, User $demoUser): void {
        // Calculate payment amount (partial payment)
        $paymentAmount = min(
            rand((int) floor($rate->remaining * 0.3), (int) floor($rate->remaining * 0.7)), // 30-70% of remaining
            $rate->remaining
        );

        if ($paymentAmount <= 0) {
            return;
        }

        try {
            // Create cash transaction (simulating cash payment)
            $cashTransaction = CashTransaction::create([
                'user_id' => $demoUser->id,
                'status' => 1, // Success
                'manufacturer_transaction_id' => null,
                'manufacturer_transaction_type' => null,
            ]);

            $sender = $assetPerson->person->phone ?? $assetPerson->person->email ?? 'Customer-'.$assetPerson->person->id;

            $transaction = new Transaction([
                'amount' => $paymentAmount,
                'type' => 'deferred_payment',
                'sender' => $sender,
                'message' => $assetPerson->device_serial,
            ]);

            $transaction->originalTransaction()->associate($cashTransaction);
            $transaction->save();

            // Update the rate remaining amount
            $rate->remaining -= $paymentAmount;
            $rate->save();

            // Dispatch PaymentSuccessEvent to create payment history
            event(new PaymentSuccessEvent(
                amount: $paymentAmount,
                paymentService: 'cash_transaction',
                paymentType: 'installment',
                sender: $sender,
                paidFor: $rate,
                payer: $assetPerson->person,
                transaction: $transaction,
            ));
        } catch (\Exception $e) {
            $this->command->warn('Failed to create partial payment transaction: '.$e->getMessage());
        }
    }

    /**
     * Create a full payment transaction for an overdue rate.
     */
    private function createFullPaymentTransaction(AssetPerson $assetPerson, AssetRate $rate, User $demoUser): void {
        if ($rate->remaining <= 0) {
            return;
        }

        $paymentAmount = $rate->remaining;

        try {
            // Create cash transaction (simulating cash payment)
            $cashTransaction = CashTransaction::create([
                'user_id' => $demoUser->id,
                'status' => 1, // Success
                'manufacturer_transaction_id' => null,
                'manufacturer_transaction_type' => null,
            ]);

            $sender = $assetPerson->person->phone ?? $assetPerson->person->email ?? 'Customer-'.$assetPerson->person->id;

            $transaction = new Transaction([
                'amount' => $paymentAmount,
                'type' => 'deferred_payment',
                'sender' => $sender,
                'message' => $assetPerson->device_serial,
            ]);

            $transaction->originalTransaction()->associate($cashTransaction);
            $transaction->save();

            // Update the rate remaining amount (fully paid)
            $rate->remaining = 0;
            $rate->save();

            // Dispatch PaymentSuccessEvent to create payment history
            event(new PaymentSuccessEvent(
                amount: $paymentAmount,
                paymentService: 'cash_transaction',
                paymentType: 'installment',
                sender: $sender,
                paidFor: $rate,
                payer: $assetPerson->person,
                transaction: $transaction,
            ));
        } catch (\Exception $e) {
            $this->command->warn('Failed to create full payment transaction: '.$e->getMessage());
        }
    }
}

<?php

namespace Database\Seeders;

use App\Events\PaymentSuccessEvent;
use App\Models\Appliance;
use App\Models\AppliancePerson;
use App\Models\ApplianceRate;
use App\Models\ApplianceType;
use App\Models\Device;
use App\Models\Meter\Meter;
use App\Models\Person\Person;
use App\Models\SolarHomeSystem;
use App\Models\Transaction\CashTransaction;
use App\Models\Transaction\Transaction;
use App\Models\User;
use App\Services\DatabaseProxyManagerService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

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
        $this->command->outputComponents()->info('Creating demo data for OutstandingDebts feature...');

        // Get existing customers and appliances
        $customers = Person::where('is_customer', true)->get();
        $applianceType = ApplianceType::where('name', 'Solar Home System')->first();
        $appliances = Appliance::where('appliance_type_id', $applianceType->id)->get();

        if ($customers->isEmpty() || $appliances->isEmpty()) {
            $this->command->outputComponents()->warn('No customers or appliances found. Skipping OutstandingDebts seeder.');

            return;
        }

        // Create AppliancePerson records with outstanding debts
        $this->createAppliancePersonWithOutstandingDebts($appliances);

        // Generate payment transactions to simulate ApplianceInstallmentPayer
        $this->generatePaymentTransactions();

        $this->command->outputComponents()->success('OutstandingDebts demo data created successfully!');
    }

    /**
     * @param Collection<int, Appliance> $appliances
     */
    private function createAppliancePersonWithOutstandingDebts(Collection $appliances): void {
        // Get existing devices (meters and SHS) that have customers
        $devices = Device::whereHasMorph('device', [Meter::class, SolarHomeSystem::class])
            ->whereHas('person', function ($query) {
                $query->where('is_customer', true);
            })
            ->with(['device', 'person'])
            ->get();

        if ($devices->isEmpty()) {
            $this->command->outputComponents()->warn('No devices found with customers. Skipping OutstandingDebts seeder.');

            return;
        }

        // Create 15-25 AppliancePerson records with outstanding debts
        $count = rand(15, 25);

        for ($i = 0; $i < $count; ++$i) {
            $device = $devices->random();
            $customer = $device->person;
            /** @var Appliance $appliance */
            $appliance = $appliances->random();

            // Create different scenarios for demo
            $scenario = rand(1, 4);

            switch ($scenario) {
                case 1:
                    // Scenario 1: Recent purchase with some overdue payments
                    $this->createRecentPurchaseScenario($customer, $appliance, $device);
                    break;
                case 2:
                    // Scenario 2: Long-term debt with multiple overdue payments
                    $this->createLongTermDebtScenario($customer, $appliance, $device);
                    break;
                case 3:
                    // Scenario 3: Partial payments with remaining balance
                    $this->createPartialPaymentScenario($customer, $appliance, $device);
                    break;
                case 4:
                    // Scenario 4: Almost paid off but with overdue final payments
                    $this->createAlmostPaidScenario($customer, $appliance, $device);
                    break;
            }
        }
    }

    private function createRecentPurchaseScenario(Person $customer, Appliance $appliance, Device $device): void {
        $appliancePerson = AppliancePerson::create([
            'appliance_id' => $appliance->id,
            'person_id' => $customer->id,
            'device_serial' => $device->device_serial,
            'total_cost' => $appliance->price,
            'rate_count' => 12,
            'down_payment' => 0,
            'first_payment_date' => Carbon::now()->subMonths(3),
            'creator_type' => 'user',
            'creator_id' => 1,
        ]);

        $this->createApplianceRatesWithRecentOverdue($appliancePerson);
    }

    private function createLongTermDebtScenario(Person $customer, Appliance $appliance, Device $device): void {
        $appliancePerson = AppliancePerson::create([
            'appliance_id' => $appliance->id,
            'person_id' => $customer->id,
            'device_serial' => $device->device_serial,
            'total_cost' => $appliance->price,
            'rate_count' => 24,
            'down_payment' => 0,
            'first_payment_date' => Carbon::now()->subMonths(18),
            'creator_type' => 'user',
            'creator_id' => 1,
        ]);

        $this->createApplianceRatesWithLongTermDebt($appliancePerson);
    }

    private function createPartialPaymentScenario(Person $customer, Appliance $appliance, Device $device): void {
        $appliancePerson = AppliancePerson::create([
            'appliance_id' => $appliance->id,
            'person_id' => $customer->id,
            'device_serial' => $device->device_serial,
            'total_cost' => $appliance->price,
            'rate_count' => 12,
            'down_payment' => 0,
            'first_payment_date' => Carbon::now()->subMonths(8),
            'creator_type' => 'user',
            'creator_id' => 1,
        ]);

        $this->createApplianceRatesWithPartialPayments($appliancePerson);
    }

    private function createAlmostPaidScenario(Person $customer, Appliance $appliance, Device $device): void {
        $appliancePerson = AppliancePerson::create([
            'appliance_id' => $appliance->id,
            'person_id' => $customer->id,
            'device_serial' => $device->device_serial,
            'total_cost' => $appliance->price,
            'rate_count' => 12,
            'down_payment' => 0,
            'first_payment_date' => Carbon::now()->subMonths(10),
            'creator_type' => 'user',
            'creator_id' => 1,
        ]);

        $this->createApplianceRatesAlmostPaid($appliancePerson);
    }

    private function createApplianceRatesWithRecentOverdue(AppliancePerson $appliancePerson): void {
        $monthlyRate = floor($appliancePerson->total_cost / $appliancePerson->rate_count);
        $remainingAmount = $appliancePerson->total_cost;

        for ($month = 1; $month <= $appliancePerson->rate_count; ++$month) {
            if ($month === $appliancePerson->rate_count) {
                $rateAmount = $remainingAmount;
            } else {
                $rateAmount = $monthlyRate;
                $remainingAmount -= $monthlyRate;
            }

            $dueDate = Carbon::parse($appliancePerson->first_payment_date)->addMonths($month);
            $isOverdue = $dueDate->isPast();

            // Recent purchase: only last 1-2 payments are overdue
            $shouldBeOverdue = $isOverdue && $month >= ($appliancePerson->rate_count - 2);
            $remaining = $shouldBeOverdue ? $rateAmount : 0;

            ApplianceRate::create([
                'appliance_person_id' => $appliancePerson->id,
                'rate_cost' => $rateAmount,
                'remaining' => $remaining,
                'due_date' => $dueDate->format('Y-m-d'),
                'remind' => $shouldBeOverdue ? rand(1, 2) : 0,
            ]);
        }
    }

    private function createApplianceRatesWithLongTermDebt(AppliancePerson $appliancePerson): void {
        $monthlyRate = floor($appliancePerson->total_cost / $appliancePerson->rate_count);
        $remainingAmount = $appliancePerson->total_cost;

        for ($month = 1; $month <= $appliancePerson->rate_count; ++$month) {
            if ($month === $appliancePerson->rate_count) {
                $rateAmount = $remainingAmount;
            } else {
                $rateAmount = $monthlyRate;
                $remainingAmount -= $monthlyRate;
            }

            $dueDate = Carbon::parse($appliancePerson->first_payment_date)->addMonths($month);
            $isOverdue = $dueDate->isPast();

            // Long-term debt: multiple overdue payments with high reminder counts
            $shouldBeOverdue = $isOverdue && rand(0, 1) === 1; // 50% chance of being overdue
            $remaining = $shouldBeOverdue ? $rateAmount : 0;

            ApplianceRate::create([
                'appliance_person_id' => $appliancePerson->id,
                'rate_cost' => $rateAmount,
                'remaining' => $remaining,
                'due_date' => $dueDate->format('Y-m-d'),
                'remind' => $shouldBeOverdue ? rand(2, 5) : 0,
            ]);
        }
    }

    private function createApplianceRatesWithPartialPayments(AppliancePerson $appliancePerson): void {
        $monthlyRate = floor($appliancePerson->total_cost / $appliancePerson->rate_count);
        $remainingAmount = $appliancePerson->total_cost;

        for ($month = 1; $month <= $appliancePerson->rate_count; ++$month) {
            if ($month === $appliancePerson->rate_count) {
                $rateAmount = $remainingAmount;
            } else {
                $rateAmount = $monthlyRate;
                $remainingAmount -= $monthlyRate;
            }

            $dueDate = Carbon::parse($appliancePerson->first_payment_date)->addMonths($month);
            $isOverdue = $dueDate->isPast();

            // Partial payments: some overdue rates have partial payments
            $shouldBeOverdue = $isOverdue && rand(0, 1) === 1;
            $hasPartialPayment = $shouldBeOverdue && rand(0, 1) === 1;
            $remaining = $shouldBeOverdue ? ($hasPartialPayment ? rand(1, (int) $rateAmount - 1) : $rateAmount) : 0;

            ApplianceRate::create([
                'appliance_person_id' => $appliancePerson->id,
                'rate_cost' => $rateAmount,
                'remaining' => $remaining,
                'due_date' => $dueDate->format('Y-m-d'),
                'remind' => $shouldBeOverdue ? rand(1, 3) : 0,
            ]);
        }
    }

    private function createApplianceRatesAlmostPaid(AppliancePerson $appliancePerson): void {
        $monthlyRate = floor($appliancePerson->total_cost / $appliancePerson->rate_count);
        $remainingAmount = $appliancePerson->total_cost;

        for ($month = 1; $month <= $appliancePerson->rate_count; ++$month) {
            if ($month === $appliancePerson->rate_count) {
                $rateAmount = $remainingAmount;
            } else {
                $rateAmount = $monthlyRate;
                $remainingAmount -= $monthlyRate;
            }

            $dueDate = Carbon::parse($appliancePerson->first_payment_date)->addMonths($month);
            $isOverdue = $dueDate->isPast();

            // Almost paid: only last 1-2 payments are overdue
            $shouldBeOverdue = $isOverdue && $month >= ($appliancePerson->rate_count - 1);
            $remaining = $shouldBeOverdue ? $rateAmount : 0;

            ApplianceRate::create([
                'appliance_person_id' => $appliancePerson->id,
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
        $this->command->outputComponents()->info('Generating payment transactions for outstanding debts...');

        // Get all AppliancePerson records with outstanding debts
        $appliancePersons = AppliancePerson::whereHas('rates', function ($query) {
            $query->where('remaining', '>', 0);
        })->with(['rates', 'person'])->get();

        if ($appliancePersons->isEmpty()) {
            $this->command->outputComponents()->warn('No AppliancePerson records with outstanding debts found. Skipping payment transaction generation.');

            return;
        }

        $this->command->outputComponents()->info("Found {$appliancePersons->count()} AppliancePerson records with outstanding debts.");

        $demoUser = User::first();

        $transactionCount = 0;
        $maxTransactions = min(50, $appliancePersons->count() * 2);

        $this->command->outputComponents()->info("Will generate up to {$maxTransactions} payment transactions.");

        foreach ($appliancePersons as $appliancePerson) {
            if ($transactionCount >= $maxTransactions) {
                break;
            }

            $outstandingRates = $appliancePerson->rates()->where('remaining', '>', 0)->get();
            $this->command->outputComponents()->twoColumnDetail(
                "AppliancePerson {$appliancePerson->id}",
                "{$outstandingRates->count()} outstanding rates"
            );

            foreach ($outstandingRates as $rate) {
                if ($transactionCount >= $maxTransactions) {
                    break;
                }

                // Create partial payment transactions (simulating customer payments)
                $this->createPartialPaymentTransaction($appliancePerson, $rate, $demoUser);
                ++$transactionCount;

                // Sometimes create full payment transactions
                if (rand(0, 3) === 0) {
                    $this->createFullPaymentTransaction($appliancePerson, $rate, $demoUser);
                    ++$transactionCount;
                }
            }
        }

        // Create historical payment transactions to simulate payment history
        $this->createHistoricalPaymentTransactions($appliancePersons, $demoUser);

        $this->command->outputComponents()->info("Generated {$transactionCount} payment transactions for outstanding debts.");
    }

    /**
     * Create historical payment transactions to simulate customer payment history.
     *
     * @param Collection<int, AppliancePerson> $appliancePersons
     * @param User                             $demoUser
     */
    private function createHistoricalPaymentTransactions(Collection $appliancePersons, User $demoUser): void {
        $historicalTransactionCount = 0;
        $maxHistoricalTransactions = 30;

        foreach ($appliancePersons as $appliancePerson) {
            if ($historicalTransactionCount >= $maxHistoricalTransactions) {
                break;
            }

            // Create 1-3 historical payments per appliance person
            $historicalPayments = rand(1, 3);

            for ($i = 0; $i < $historicalPayments; ++$i) {
                if ($historicalTransactionCount >= $maxHistoricalTransactions) {
                    break;
                }

                $this->createHistoricalPaymentTransaction($appliancePerson, $demoUser, $i);
                ++$historicalTransactionCount;
            }
        }

        $this->command->outputComponents()->info("Created {$historicalTransactionCount} historical payment transactions.");
    }

    /**
     * Create a historical payment transaction.
     */
    private function createHistoricalPaymentTransaction(AppliancePerson $appliancePerson, User $demoUser, int $paymentIndex): void {
        try {
            // Calculate a historical date (1-12 months ago)
            $monthsAgo = rand(1, 12);
            $historicalDate = Carbon::now()->subMonths($monthsAgo);

            // Get sender information
            $sender = $appliancePerson->person->phone ?? $appliancePerson->person->email ?? 'Customer-'.$appliancePerson->person->id;

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
                'message' => $appliancePerson->device_serial,
                'created_at' => $historicalDate,
                'updated_at' => $historicalDate,
            ]);

            // Associate the cash transaction using Laravel's polymorphic relationship
            $transaction->originalTransaction()->associate($cashTransaction);
            $transaction->save();

            // Find a rate that would have been due around this time
            $dueDate = Carbon::parse($appliancePerson->first_payment_date)->addMonths($paymentIndex + 1);

            // Create a historical ApplianceRate record if it doesn't exist
            $historicalRate = ApplianceRate::where('appliance_person_id', $appliancePerson->id)
                ->where('due_date', $dueDate->format('Y-m-d'))
                ->first();

            if (!$historicalRate) {
                // Create a historical rate record
                $monthlyRate = floor($appliancePerson->total_cost / $appliancePerson->rate_count);
                $historicalRate = ApplianceRate::create([
                    'appliance_person_id' => $appliancePerson->id,
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
                payer: $appliancePerson->person,
                transaction: $transaction,
            ));
        } catch (\Exception $e) {
            $this->command->outputComponents()->warn('Failed to create historical payment transaction: '.$e->getMessage());
        }
    }

    /**
     * Create a partial payment transaction for an overdue rate.
     */
    private function createPartialPaymentTransaction(AppliancePerson $appliancePerson, ApplianceRate $rate, User $demoUser): void {
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

            $sender = $appliancePerson->person->phone ?? $appliancePerson->person->email ?? 'Customer-'.$appliancePerson->person->id;

            $transaction = new Transaction([
                'amount' => $paymentAmount,
                'type' => 'deferred_payment',
                'sender' => $sender,
                'message' => $appliancePerson->device_serial,
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
                payer: $appliancePerson->person,
                transaction: $transaction,
            ));
        } catch (\Exception $e) {
            $this->command->outputComponents()->warn('Failed to create partial payment transaction: '.$e->getMessage());
        }
    }

    /**
     * Create a full payment transaction for an overdue rate.
     */
    private function createFullPaymentTransaction(AppliancePerson $appliancePerson, ApplianceRate $rate, User $demoUser): void {
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

            $sender = $appliancePerson->person->phone ?? $appliancePerson->person->email ?? 'Customer-'.$appliancePerson->person->id;

            $transaction = new Transaction([
                'amount' => $paymentAmount,
                'type' => 'deferred_payment',
                'sender' => $sender,
                'message' => $appliancePerson->device_serial,
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
                payer: $appliancePerson->person,
                transaction: $transaction,
            ));
        } catch (\Exception $e) {
            $this->command->outputComponents()->warn('Failed to create full payment transaction: '.$e->getMessage());
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetPerson;
use App\Models\AssetRate;
use App\Models\AssetType;
use App\Models\Person\Person;
use App\Services\ApplianceRateService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use MPM\DatabaseProxy\DatabaseProxyManagerService;

class OutstandingDebtsSeeder extends Seeder {
    public function __construct(
        private DatabaseProxyManagerService $databaseProxyManagerService,
        private ApplianceRateService $applianceRateService,
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
        $this->createAssetPersonWithOutstandingDebts($customers, $assets);

        $this->command->info('OutstandingDebts demo data created successfully!');
    }

    private function createAssetPersonWithOutstandingDebts($customers, $assets): void {
        // Get existing devices (meters and SHS) that have customers
        $devices = \App\Models\Device::whereHasMorph('device', [\App\Models\Meter\Meter::class, \App\Models\SolarHomeSystem::class])
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

    private function createRecentPurchaseScenario($customer, $asset, $device): void {
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

    private function createLongTermDebtScenario($customer, $asset, $device): void {
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

    private function createPartialPaymentScenario($customer, $asset, $device): void {
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

    private function createAlmostPaidScenario($customer, $asset, $device): void {
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
}

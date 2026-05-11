<?php

namespace Database\Seeders;

use App\Events\PaymentSuccessEvent;
use App\Models\Appliance;
use App\Models\AppliancePerson;
use App\Models\ApplianceRate;
use App\Models\ApplianceType;
use App\Models\Device;
use App\Models\Meter\Meter;
use App\Models\SolarHomeSystem;
use App\Models\Transaction\CashTransaction;
use App\Models\Transaction\Transaction;
use App\Models\User;
use App\Services\DatabaseProxyManagerService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class OutstandingDebtsSeeder extends Seeder {
    public function __construct(
        private DatabaseProxyManagerService $databaseProxyManagerService,
    ) {
        $this->databaseProxyManagerService->buildDatabaseConnectionDemoCompany();
    }

    public function run(): void {
        $this->command->outputComponents()->info('Creating demo data for OutstandingDebts feature...');

        $applianceType = ApplianceType::where('name', 'Solar Home System')->first();
        $appliances = Appliance::where('appliance_type_id', $applianceType->id)->get();

        $devices = Device::whereHasMorph('device', [Meter::class, SolarHomeSystem::class])
            ->whereHas('person', function ($query) {
                $query->where('is_customer', true);
            })
            ->with(['device', 'person'])
            ->get();

        if ($appliances->isEmpty() || $devices->isEmpty()) {
            $this->command->outputComponents()->warn('No appliances or devices with customers found. Skipping OutstandingDebts seeder.');

            return;
        }

        $demoUser = User::first();
        $count = rand(15, 25);

        for ($i = 0; $i < $count; ++$i) {
            $device = $devices->random();
            /** @var Appliance $appliance */
            $appliance = $appliances->random();

            switch (rand(1, 4)) {
                case 1:
                    $this->createRecentPurchaseScenario($device, $appliance, $demoUser);
                    break;
                case 2:
                    $this->createLongTermDebtScenario($device, $appliance, $demoUser);
                    break;
                case 3:
                    $this->createPartialPaymentScenario($device, $appliance, $demoUser);
                    break;
                case 4:
                    $this->createAlmostPaidScenario($device, $appliance, $demoUser);
                    break;
            }
        }

        $this->command->outputComponents()->success('OutstandingDebts demo data created successfully!');
    }

    /**
     * Scenario 1: Recent purchase — 3 months in on a 12-month plan.
     * Customer has paid the first 1-2 installments; the most recent due rate is overdue.
     */
    private function createRecentPurchaseScenario(Device $device, Appliance $appliance, User $demoUser): void {
        $appliancePerson = $this->createAppliancePerson($device, $appliance, rateCount: 12, monthsAgo: 3);

        $rates = $this->createApplianceRates(
            appliancePerson: $appliancePerson,
            paidCount: rand(1, 2),
            partialAmount: null,
        );

        $this->recordPaymentsForPaidRates($appliancePerson, $rates, $demoUser);
    }

    /**
     * Scenario 2: Long-term debt — 18 months in on a 24-month plan.
     * Customer paid the first 10-14 installments, then fell behind; many past rates remain
     * overdue and the remaining future rates are still pending.
     */
    private function createLongTermDebtScenario(Device $device, Appliance $appliance, User $demoUser): void {
        $appliancePerson = $this->createAppliancePerson($device, $appliance, rateCount: 24, monthsAgo: 18);

        $monthlyRate = (int) floor($appliancePerson->total_cost / $appliancePerson->rate_count);
        $partialAmount = rand(0, 1) === 1
            ? rand((int) ($monthlyRate * 0.3), (int) ($monthlyRate * 0.7))
            : null;

        $rates = $this->createApplianceRates(
            appliancePerson: $appliancePerson,
            paidCount: rand(10, 14),
            partialAmount: $partialAmount,
        );

        $this->recordPaymentsForPaidRates($appliancePerson, $rates, $demoUser);
    }

    /**
     * Scenario 3: Partial payments — 8 months in on a 12-month plan.
     * First few installments paid in full, the current rate is only part-paid,
     * later rates (overdue and future) remain unpaid.
     */
    private function createPartialPaymentScenario(Device $device, Appliance $appliance, User $demoUser): void {
        $appliancePerson = $this->createAppliancePerson($device, $appliance, rateCount: 12, monthsAgo: 8);

        $monthlyRate = (int) floor($appliancePerson->total_cost / $appliancePerson->rate_count);
        $partialAmount = rand((int) ($monthlyRate * 0.3), (int) ($monthlyRate * 0.8));

        $rates = $this->createApplianceRates(
            appliancePerson: $appliancePerson,
            paidCount: rand(4, 5),
            partialAmount: $partialAmount,
        );

        $this->recordPaymentsForPaidRates($appliancePerson, $rates, $demoUser);
    }

    /**
     * Scenario 4: Almost paid off — 10 months in on a 12-month plan.
     * Customer has paid nearly all installments, with only the last 1-2 overdue.
     */
    private function createAlmostPaidScenario(Device $device, Appliance $appliance, User $demoUser): void {
        $appliancePerson = $this->createAppliancePerson($device, $appliance, rateCount: 12, monthsAgo: 10);

        $rates = $this->createApplianceRates(
            appliancePerson: $appliancePerson,
            paidCount: rand(9, 10),
            partialAmount: null,
        );

        $this->recordPaymentsForPaidRates($appliancePerson, $rates, $demoUser);
    }

    private function createAppliancePerson(Device $device, Appliance $appliance, int $rateCount, int $monthsAgo): AppliancePerson {
        return AppliancePerson::create([
            'appliance_id' => $appliance->id,
            'person_id' => $device->person->id,
            'device_serial' => $device->device_serial,
            'total_cost' => $appliance->price,
            'rate_count' => $rateCount,
            'down_payment' => 0,
            'first_payment_date' => Carbon::now()->subMonths($monthsAgo),
            'creator_type' => 'user',
            'creator_id' => 1,
        ]);
    }

    /**
     * Create rates in a chronologically consistent state:
     * - rates 1..paidCount are fully paid (remaining = 0)
     * - rate paidCount+1 is partially paid if $partialAmount is provided
     * - all later rates are unpaid (remaining = rate_cost); past ones appear as overdue,
     *   future ones as upcoming.
     *
     * Returns the created rates keyed by index so the caller can record backdated
     * payment transactions against the paid ones.
     *
     * @return array<int, array{rate: ApplianceRate, paidAmount: int, dueDate: Carbon}>
     */
    private function createApplianceRates(AppliancePerson $appliancePerson, int $paidCount, ?int $partialAmount): array {
        $rateCount = $appliancePerson->rate_count;
        $monthlyRate = (int) floor($appliancePerson->total_cost / $rateCount);
        // Last rate absorbs any rounding remainder so sum(rates) == total_cost.
        $finalRate = $appliancePerson->total_cost - $monthlyRate * ($rateCount - 1);
        $firstPaymentDate = Carbon::parse($appliancePerson->first_payment_date);

        $created = [];

        for ($month = 1; $month <= $rateCount; ++$month) {
            $rateAmount = $month === $rateCount ? $finalRate : $monthlyRate;
            $dueDate = $firstPaymentDate->copy()->addMonths($month);

            if ($month <= $paidCount) {
                $remaining = 0;
                $paidAmount = $rateAmount;
            } elseif ($month === $paidCount + 1 && $partialAmount !== null) {
                // Clamp into (0, rateAmount) so the rate is genuinely partial.
                $remaining = max(1, min($partialAmount, $rateAmount - 1));
                $paidAmount = $rateAmount - $remaining;
            } else {
                $remaining = $rateAmount;
                $paidAmount = 0;
            }

            $isOverdue = $dueDate->isPast() && $remaining > 0;

            $rate = ApplianceRate::create([
                'appliance_person_id' => $appliancePerson->id,
                'rate_cost' => $rateAmount,
                'remaining' => $remaining,
                'due_date' => $dueDate->format('Y-m-d'),
                'remind' => $isOverdue ? rand(1, 3) : 0,
            ]);

            $created[] = ['rate' => $rate, 'paidAmount' => $paidAmount, 'dueDate' => $dueDate];
        }

        return $created;
    }

    /**
     * @param array<int, array{rate: ApplianceRate, paidAmount: int, dueDate: Carbon}> $rates
     */
    private function recordPaymentsForPaidRates(AppliancePerson $appliancePerson, array $rates, User $demoUser): void {
        foreach ($rates as $entry) {
            if ($entry['paidAmount'] <= 0) {
                continue;
            }
            $this->recordPayment($appliancePerson, $entry['rate'], $demoUser, $entry['paidAmount'], $entry['dueDate']);
        }
    }

    /**
     * Create a backdated CashTransaction + Transaction near the rate's due date and
     * dispatch PaymentSuccessEvent so payment history is written. Does not mutate
     * ApplianceRate->remaining (already set when the rate was created).
     */
    private function recordPayment(
        AppliancePerson $appliancePerson,
        ApplianceRate $rate,
        User $demoUser,
        int $amount,
        Carbon $dueDate,
    ): void {
        $paidAt = $dueDate->copy()->addDays(rand(-3, 5));
        if ($paidAt->isFuture()) {
            $paidAt = Carbon::now()->subDays(rand(0, 3));
        }

        $sender = $appliancePerson->person->phone
            ?? $appliancePerson->person->email
            ?? 'Customer-'.$appliancePerson->person->id;

        $cashTransaction = CashTransaction::create([
            'user_id' => $demoUser->id,
            'status' => 1,
            'manufacturer_transaction_id' => null,
            'manufacturer_transaction_type' => null,
            'created_at' => $paidAt,
            'updated_at' => $paidAt,
        ]);

        $transaction = new Transaction([
            'amount' => $amount,
            'type' => Transaction::TYPE_DEFERRED_PAYMENT,
            'sender' => $sender,
            'message' => $appliancePerson->device_serial,
            'created_at' => $paidAt,
            'updated_at' => $paidAt,
        ]);
        $transaction->originalTransaction()->associate($cashTransaction);
        $transaction->save();

        event(new PaymentSuccessEvent(
            amount: $amount,
            paymentService: 'cash_transaction',
            paymentType: 'installment',
            sender: $sender,
            paidFor: $rate,
            payer: $appliancePerson->person,
            transaction: $transaction,
        ));
    }
}

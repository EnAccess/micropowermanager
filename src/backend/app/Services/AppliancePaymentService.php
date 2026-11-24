<?php

namespace App\Services;

use App\Events\NewLogEvent;
use App\Events\PaymentSuccessEvent;
use App\Exceptions\PayerPhoneNotFoundException;
use App\Exceptions\PaymentAmountBiggerThanTotalRemainingAmount;
use App\Exceptions\PaymentAmountSmallerThanZero;
use App\Models\AppliancePerson;
use App\Models\ApplianceRate;
use App\Models\MainSettings;
use App\Models\Transaction\BasePaymentProviderTransaction;
use App\Models\Transaction\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class AppliancePaymentService {
    public const int DEFAULT_DOWN_PAYMENT_MAX_TOKEN_DAYS = 30;

    public float $paymentAmount;
    public bool $applianceInstallmentsFullFilled = false;

    public function __construct(
        private MainSettings $mainSettings,
        private PaymentInitiationService $paymentInitiationService,
        private PersonService $personService,
    ) {}

    public function updateRateRemaining(int $id, float $amount): ApplianceRate {
        $applianceRate = ApplianceRate::query()->findOrFail($id);
        $applianceRate->remaining -= (int) $amount; // Cast to int to match property type
        $applianceRate->update();
        $applianceRate->save();

        return $applianceRate;
    }

    public function createPaymentLog(AppliancePerson $appliancePerson, float $amount, int $creatorId): void {
        $mainSettings = $this->mainSettings->newQuery()->first();
        $currency = $mainSettings->currency ?? '€';
        event(new NewLogEvent([
            'user_id' => $creatorId,
            'affected' => $appliancePerson,
            'action' => $amount.' '.$currency.' of payment is made ',
        ]));
    }

    public function createPaymentHistory(float $amount, AppliancePerson $buyer, ApplianceRate $applianceRate, Transaction $transaction): void {
        $paymentType = $transaction->type === Transaction::TYPE_DOWN_PAYMENT
            ? Transaction::TYPE_DOWN_PAYMENT
            : 'installment';

        event(new PaymentSuccessEvent(
            amount: (int) $amount,
            paymentService: $transaction->original_transaction_type,
            paymentType: $paymentType,
            sender: $transaction->sender,
            paidFor: $applianceRate,
            payer: $buyer->person,
            transaction: $transaction,
        ));
    }

    /**
     * Starts a payment towards the installment plan of a sold appliance, with cash or with any
     * provider the tenant has enabled. Shared by the admin panel and the field app, so the
     * transaction that comes out is identical either way; `$agentId` is what records that an agent
     * initiated it, and is null for a payment made from the admin panel.
     *
     * The caller resolves `$applianceDetail` (via AppliancePersonService::getSoldApplianceDetails)
     * and dispatches ProcessPayment after committing — the provider charge can be the last thing
     * to happen before the commit, so nothing reversible may follow it here.
     *
     * @return array{transaction: Transaction, provider_data: array<string, mixed>, process_immediately: bool}
     */
    public function initiateInstallmentPayment(
        AppliancePerson $applianceDetail,
        float $amount,
        int $providerId,
        ?string $payerPhoneOverride = null,
        ?int $agentId = null,
    ): array {
        $this->validateAmount($applianceDetail, $amount);

        $applianceOwner = $applianceDetail->person;

        if (!$applianceOwner) {
            throw new PayerPhoneNotFoundException('Appliance owner not found, so the payer could not be determined.');
        }

        $payerPhone = $payerPhoneOverride ?? $this->personService->getPrimaryPhoneNumber($applianceOwner);
        $deviceSerial = $applianceDetail->device_serial;

        $result = $this->paymentInitiationService->initiate(
            providerId: $providerId,
            amount: $amount,
            sender: $payerPhone ?? '-',
            message: $deviceSerial ?? (string) $applianceDetail->id,
            type: Transaction::TYPE_DEFERRED_PAYMENT,
            customerId: $applianceOwner->id,
            serialId: $deviceSerial,
        );

        if ($agentId !== null) {
            $result['transaction']->agent_id = $agentId;
            $result['transaction']->save();
        }

        return $result;
    }

    public function validateAmount(AppliancePerson $applianceDetail, float $amount): void {
        if ($amount <= 0) {
            throw new PaymentAmountSmallerThanZero('Payment amount can not smaller than zero');
        }

        if ($applianceDetail->isEnergyService()) {
            $minimumPayableAmount = $applianceDetail->minimum_payable_amount ?? 0;
            if ($minimumPayableAmount > 0 && $amount < $minimumPayableAmount) {
                throw new PaymentAmountSmallerThanZero("Payment amount can not be less than minimum payable amount ({$minimumPayableAmount})");
            }

            return;
        }

        $totalRemainingAmount = $applianceDetail->rates->sum('remaining');
        $installmentCost = $this->getNextPayableInstallmentAmount($applianceDetail->rates);

        if ($amount > $totalRemainingAmount) {
            throw new PaymentAmountBiggerThanTotalRemainingAmount('Payment Amount can not bigger than Total Remaining Amount');
        }

        if ($amount < $installmentCost && $amount != $totalRemainingAmount) {
            throw new PaymentAmountSmallerThanZero('Payment amount can not smaller than installment cost');
        }
    }

    /**
     * The balance still owed on the next outstanding installment — the smallest payment
     * we accept, since payments waterfall onto the earliest unpaid rate. Shrinks as that
     * rate is paid down, so it is a payment floor only, never a price.
     *
     * @param Collection<int, ApplianceRate> $rates
     */
    public function getNextPayableInstallmentAmount(Collection $rates): float {
        return (float) ($this->nextPayableRate($rates)->remaining ?? 0);
    }

    /**
     * The scheduled cost of the next outstanding installment — what one installment
     * period costs under the plan, unaffected by how much of that rate is already
     * settled. Token day math divides by this, so it must not shrink as the customer
     * pays: doing so collapses the per-day price and inflates the issued token.
     *
     * @param Collection<int, ApplianceRate> $rates
     */
    public function getNextPayableInstallmentCost(Collection $rates): float {
        return (float) ($this->nextPayableRate($rates)->rate_cost ?? 0);
    }

    /**
     * The earliest rate still carrying a balance.
     *
     * @param Collection<int, ApplianceRate> $rates
     */
    private function nextPayableRate(Collection $rates): ?ApplianceRate {
        return $this->inScheduleOrder($rates)->first(fn (ApplianceRate $rate): bool => $rate->remaining > 0);
    }

    public function isDownPaymentOutstanding(AppliancePerson $appliancePerson): bool {
        $paid = $appliancePerson->rates->sum(fn (ApplianceRate $rate): int => $rate->rate_cost - $rate->remaining);

        return $appliancePerson->down_payment > 0 && $paid === 0;
    }

    public function isOutstandingDownPaymentRate(ApplianceRate $rate): bool {
        $appliancePerson = $rate->appliancePerson;

        return $this->isDownPaymentOutstanding($appliancePerson)
            && $this->inScheduleOrder($appliancePerson->rates)->first()?->is($rate) === true;
    }

    /**
     * The rates the plan's price per day is derived from. The down payment is not an
     * installment, so it is left out while it is the next rate to be paid.
     *
     * @return Collection<int, ApplianceRate>
     */
    public function scheduledInstallments(AppliancePerson $appliancePerson): Collection {
        $rates = $this->inScheduleOrder($appliancePerson->rates);

        return $this->isDownPaymentOutstanding($appliancePerson) ? $rates->slice(1)->values() : $rates;
    }

    /**
     * The rates in schedule order, re-keyed from zero. The rates relation is
     * unordered and rescheduling recreates rows, so insertion order does not
     * reliably follow the schedule; due date does.
     *
     * @param Collection<int, ApplianceRate> $rates
     *
     * @return Collection<int, ApplianceRate>
     */
    public function inScheduleOrder(Collection $rates): Collection {
        return $rates
            ->sortBy(fn (ApplianceRate $rate): int => Carbon::parse($rate->due_date)->getTimestamp())
            ->values();
    }

    public function payInstallment(ApplianceRate $installment, AppliancePerson $applianceOwner, Transaction $transaction): void {
        if ($installment['remaining'] > 0 && $this->paymentAmount > 0) {
            if ($installment['remaining'] <= $this->paymentAmount) {
                $this->paymentAmount -= $installment['remaining'];
                $applianceRate = $this->updateRateRemaining($installment['id'], (float) $installment['remaining']);
                $this->createPaymentHistory($installment['remaining'], $applianceOwner, $applianceRate, $transaction);
            } else {
                $applianceRate = $this->updateRateRemaining($installment['id'], $this->paymentAmount);
                $this->createPaymentHistory($this->paymentAmount, $applianceOwner, $applianceRate, $transaction);
                $this->paymentAmount = 0;
            }
        }
    }

    /**
     * The longest a down payment token may run. A deposit is sold as an introductory period
     * rather than as the days its size happens to buy, so operators set the period they sell
     * and a large deposit is held to it.
     *
     * Any value that is not a positive number of days means the operator has not set one.
     */
    public function downPaymentMaxTokenDays(): float {
        $mainSettings = $this->mainSettings->newQuery()->first();
        $configured = (int) ($mainSettings->down_payment_max_token_days ?? 0);

        return (float) ($configured > 0 ? $configured : self::DEFAULT_DOWN_PAYMENT_MAX_TOKEN_DAYS);
    }

    /**
     * The whole days a payment buys. Token vending and the installment schedule both use it,
     * so a down payment never buys more days than the schedule leaves before the first
     * installment is due. Multiplying before dividing, plus the rounding guard, keeps float
     * noise from bumping an exact period up a day.
     */
    public static function creditDays(float $amount, float $installmentCost, float $periodDays, ?float $maxDays = null): float {
        $days = ceil(round($amount * $periodDays / $installmentCost, 6));

        return $maxDays === null ? $days : min($days, $maxDays);
    }

    /**
     * What one day of usage costs under the customer's plan. Manufacturer APIs that
     * vend time divide a payment by this to get the number of days to grant, and
     * ad-hoc token generation multiplies by it to go the other way.
     */
    public function getDailyPrice(AppliancePerson $appliancePerson): float {
        if ($appliancePerson->isEnergyService()) {
            return (float) ($appliancePerson->price_per_day ?? 0);
        }

        return $this->getNextPayableInstallmentCost($this->scheduledInstallments($appliancePerson))
            / $this->getInstallmentPeriodDays($appliancePerson);
    }

    /**
     * The days the installment being paid right now covers: one period of the plan's rate
     * type, starting on the due date of the same installment the daily price is costed on.
     */
    public function getInstallmentPeriodDays(AppliancePerson $appliancePerson): float {
        $rateType = $appliancePerson->rate_type
            ?? throw new \LogicException("Installment plan {$appliancePerson->id} has no rate type.");
        $nextPayableRate = $this->nextPayableRate($this->scheduledInstallments($appliancePerson));
        $periodStart = $nextPayableRate instanceof ApplianceRate ? Carbon::parse($nextPayableRate->due_date) : Carbon::today();

        return (float) $rateType->periodDaysFrom($periodStart);
    }

    /**
     * @return array{status: 'processing'|'processed'|'failed', processed: bool, transaction_id: int}
     */
    public function checkPaymentStatus(Transaction $transaction): array {
        $processed = $transaction->paymentHistories()->exists();
        $originalTransaction = $transaction->originalTransaction()->first();

        // Providers that confirm asynchronously (a webhook rather than a synchronous push) leave a
        // rejected payment with no payment history, which is indistinguishable from one still in
        // the queue. The provider row's status is the only place the rejection is recorded, so
        // report it — otherwise the client polls a dead transaction until it gives up.
        $failed = !$processed
            && $originalTransaction instanceof BasePaymentProviderTransaction
            && $originalTransaction->status === BasePaymentProviderTransaction::STATUS_FAILED;

        return [
            'status' => match (true) {
                $processed => 'processed',
                $failed => 'failed',
                default => 'processing',
            },
            'processed' => $processed,
            'transaction_id' => $transaction->id,
        ];
    }
}

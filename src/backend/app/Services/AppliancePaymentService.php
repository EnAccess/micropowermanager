<?php

namespace App\Services;

use App\Events\NewLogEvent;
use App\Events\PaymentSuccessEvent;
use App\Exceptions\PaymentAmountBiggerThanTotalRemainingAmount;
use App\Exceptions\PaymentAmountSmallerThanZero;
use App\Models\AppliancePerson;
use App\Models\ApplianceRate;
use App\Models\MainSettings;
use App\Models\Transaction\Transaction;
use Carbon\Carbon;
use Carbon\Month;
use Carbon\WeekDay;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class AppliancePaymentService {
    public const DEFAULT_DAY_DIFFERENCE_BETWEEN_INSTALLMENTS = 30;

    public float $paymentAmount;
    public bool $applianceInstallmentsFullFilled = false;

    public function __construct(private MainSettings $mainSettings) {}

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
        event(new PaymentSuccessEvent(
            amount: (int) $amount,
            paymentService: 'web',
            paymentType: 'installment',
            sender: $transaction->sender,
            paidFor: $applianceRate,
            payer: $buyer->person,
            transaction: $transaction,
        ));
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
     * The earliest rate still carrying a balance. Sorted by due date because the
     * rates relation is unordered and rescheduling recreates rows, so insertion
     * order does not reliably follow the schedule.
     *
     * @param Collection<int, ApplianceRate> $rates
     */
    private function nextPayableRate(Collection $rates): ?ApplianceRate {
        return $rates
            ->sortBy(fn (ApplianceRate $rate): int => Carbon::parse($rate->due_date)->getTimestamp())
            ->first(fn (ApplianceRate $rate): bool => $rate->remaining > 0);
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
     * @param Collection<int, ApplianceRate> $installments
     */
    public function getDayDifferenceBetweenTwoInstallments(Collection $installments): float {
        try {
            $dueDates = $installments
                ->map(fn (ApplianceRate $installment) => $installment->due_date)
                ->filter()
                ->map(fn (\DateTimeInterface|WeekDay|Month|string|int|float|null $dueDate): Carbon => Carbon::parse($dueDate))
                ->sort()
                ->values();

            if ($dueDates->count() < 3) {
                return self::DEFAULT_DAY_DIFFERENCE_BETWEEN_INSTALLMENTS;
            }

            $dayDifference = (int) $dueDates[1]->diffInDays($dueDates[2], absolute: true);
        } catch (\Exception $e) {
            Log::warning('Falling back to the default installment cadence.', ['message' => $e->getMessage()]);

            return self::DEFAULT_DAY_DIFFERENCE_BETWEEN_INSTALLMENTS;
        }

        return $dayDifference > 0 ? $dayDifference : self::DEFAULT_DAY_DIFFERENCE_BETWEEN_INSTALLMENTS;
    }

    /**
     * @return array{status: 'processing'|'processed', processed: bool, transaction_id: int}
     */
    public function checkPaymentStatus(Transaction $transaction): array {
        $processed = $transaction->paymentHistories()->exists();

        return [
            'status' => $processed ? 'processed' : 'processing',
            'processed' => $processed,
            'transaction_id' => $transaction->id,
        ];
    }
}

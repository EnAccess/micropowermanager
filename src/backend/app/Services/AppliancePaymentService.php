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
use Illuminate\Database\Eloquent\Collection;

class AppliancePaymentService {
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
     * The amount needed to settle the next outstanding installment — the smallest
     * payment we accept, since payments waterfall onto the earliest unpaid rate.
     * Uses the next unpaid rate's remaining rather than a fixed position, so it stays
     * correct when the schedule holds uneven or already-settled rates.
     *
     * @param Collection<int, ApplianceRate> $rates
     */
    public function getNextPayableInstallmentAmount(Collection $rates): float {
        foreach ($rates as $rate) {
            if ($rate->remaining > 0) {
                return (float) $rate->remaining;
            }
        }

        return 0.0;
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
            $secondInstallment = $installments[1];
            $thirdInstallment = $installments[2];

            if (!$secondInstallment || !$thirdInstallment) {
                return 30;
            }

            $secondDueDate = $secondInstallment->due_date ?? null;
            $thirdDueDate = $thirdInstallment->due_date ?? null;

            if (!$secondDueDate || !$thirdDueDate) {
                return 30;
            }

            $dueDateSecondRow = Carbon::parse($secondDueDate);
            $dueDateThirdRow = Carbon::parse($thirdDueDate);

            return (int) $dueDateSecondRow->diffInDays($dueDateThirdRow);
        } catch (\Exception) {
            return 30;
        }
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

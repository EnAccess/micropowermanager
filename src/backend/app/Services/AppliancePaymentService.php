<?php

namespace App\Services;

use App\Events\NewLogEvent;
use App\Events\PaymentSuccessEvent;
use App\Exceptions\PaymentAmountBiggerThanTotalRemainingAmount;
use App\Exceptions\PaymentAmountSmallerThanZero;
use App\Jobs\ProcessPayment;
use App\Models\AppliancePerson;
use App\Models\ApplianceRate;
use App\Models\MainSettings;
use App\Models\Transaction\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class AppliancePaymentService {
    private float $paymentAmount;
    public bool $applianceInstallmentsFullFilled = false;

    public function __construct(private CashTransactionService $cashTransactionService, private MainSettings $mainSettings, private AppliancePersonService $appliancePersonService) {}

    /**
     * @return array<string, mixed>
     */
    public function getPaymentForAppliance(Request $request, AppliancePerson $appliancePerson): array {
        $creatorId = auth('api')->user()->id;
        $this->paymentAmount = $amount = (float) $request->input('amount');
        $applianceDetail = $this->appliancePersonService->getApplianceDetails($appliancePerson->id);
        $this->validateAmount($applianceDetail, $amount);
        $deviceSerial = $applianceDetail->device_serial;
        $applianceOwner = $appliancePerson->person;
        $companyId = $request->attributes->get('companyId');

        if (!$applianceOwner) {
            throw new \InvalidArgumentException('Appliance owner not found');
        }

        $ownerAddress = $applianceOwner->addresses()->where('is_primary', 1)->first();
        $sender = $ownerAddress == null ? '-' : $ownerAddress->phone;
        $transaction =
            $this->cashTransactionService->createCashTransaction($creatorId, $amount, $sender, $deviceSerial, $appliancePerson->id);

        dispatch(new ProcessPayment($companyId, $transaction->id));

        return [
            'appliance_person' => $appliancePerson,
            'transaction_id' => $transaction->id,
        ];
    }

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

    private function validateAmount(AppliancePerson $applianceDetail, float $amount): void {
        $totalRemainingAmount = $applianceDetail->rates->sum('remaining');
        $installmentCost = $applianceDetail->rates[1]['rate_cost'] ?? 0;

        if ($amount > $totalRemainingAmount) {
            throw new PaymentAmountBiggerThanTotalRemainingAmount('Payment Amount can not bigger than Total Remaining Amount');
        }

        if ($amount < $installmentCost && $amount != $totalRemainingAmount) {
            throw new PaymentAmountSmallerThanZero('Payment amount can not smaller than installment cost');
        }

        if ($amount <= 0) {
            throw new PaymentAmountSmallerThanZero('Payment amount can not smaller than zero');
        }
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

    public function setPaymentAmount(float $amount): void {
        $this->paymentAmount = $amount;
    }

    /**
     * @return array<string, mixed>
     */
    public function checkPaymentStatus(int $transactionId): array {
        $transaction = Transaction::query()->find($transactionId);

        if (!$transaction) {
            return [
                'status' => 'not_found',
                'processed' => false,
            ];
        }

        $hasPaymentHistories = $transaction->paymentHistories()->exists();

        return [
            'status' => $hasPaymentHistories ? 'processed' : 'processing',
            'processed' => $hasPaymentHistories,
            'transaction_id' => $transactionId,
        ];
    }
}

<?php

namespace App\Services;

use App\Events\NewLogEvent;
use App\Events\PaymentSuccessEvent;
use App\Exceptions\PaymentAmountBiggerThanTotalRemainingAmount;
use App\Exceptions\PaymentAmountSmallerThanZero;
use App\Misc\TransactionDataContainer;
use App\Models\AssetPerson;
use App\Models\AssetRate;
use App\Models\MainSettings;
use App\Models\Token;
use App\Models\Transaction\Transaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use MPM\Device\DeviceService;

class AppliancePaymentService {
    private float $paymentAmount;
    public bool $applianceInstallmentsFullFilled;

    public function __construct(
        private CashTransactionService $cashTransactionService,
        private MainSettings $mainSettings,
        private AppliancePersonService $appliancePersonService,
        private DeviceService $deviceService,
    ) {
        $this->applianceInstallmentsFullFilled = false;
    }

    public function getPaymentForAppliance(Request $request, AssetPerson $appliancePerson): AssetPerson {
        $creatorId = auth('api')->user()->id;
        $this->paymentAmount = $amount = (float) $request->input('amount');
        $applianceDetail = $this->appliancePersonService->getApplianceDetails($appliancePerson->id);
        $this->validateAmount($applianceDetail, $amount);
        $deviceSerial = $applianceDetail->device_serial;
        $applianceOwner = $appliancePerson->person;

        if (!$applianceOwner) {
            throw new \InvalidArgumentException('Appliance owner not found');
        }

        $ownerAddress = $applianceOwner->addresses()->where('is_primary', 1)->first();
        $sender = $ownerAddress == null ? '-' : $ownerAddress->phone;
        $transaction =
            $this->cashTransactionService->createCashTransaction($creatorId, $amount, $sender, $deviceSerial);
        $totalRemainingAmount = $applianceDetail->rates->sum('remaining');
        $this->applianceInstallmentsFullFilled = $totalRemainingAmount <= $amount;
        $applianceDetail->rates->map(fn ($installment) => $this->payInstallment(
            $installment,
            $appliancePerson, // Changed from $applianceOwner to $appliancePerson
            $transaction
        ));
        if ($applianceDetail->device_serial) {
            $this->processPaymentForDevice($deviceSerial, $transaction, $applianceDetail);
        } else {
            $this->createPaymentLog($appliancePerson, $amount, $creatorId);
        }

        return $appliancePerson;
    }

    public function updateRateRemaining(int $id, float $amount): AssetRate {
        /** @var AssetRate $applianceRate */
        $applianceRate = AssetRate::query()->findOrFail($id);
        $applianceRate->remaining -= (int) $amount; // Cast to int to match property type
        $applianceRate->update();
        $applianceRate->save();

        return $applianceRate;
    }

    public function createPaymentLog(AssetPerson $appliancePerson, float $amount, int $creatorId): void {
        /** @var MainSettings $mainSettings */
        $mainSettings = $this->mainSettings->newQuery()->first();
        $currency = $mainSettings->currency ?? '€';
        event(new NewLogEvent([
            'user_id' => $creatorId,
            'affected' => $appliancePerson,
            'action' => $amount.' '.$currency.' of payment is made ',
        ]));
    }

    public function createPaymentHistory(float $amount, AssetPerson $buyer, AssetRate $applianceRate, Transaction $transaction): void {
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

    private function validateAmount(AssetPerson $applianceDetail, float $amount): void {
        $totalRemainingAmount = $applianceDetail->rates->sum('remaining');
        $installmentCost = $applianceDetail->rates[1]['rate_cost'] ?? 0;

        if ($amount > $totalRemainingAmount) {
            throw new PaymentAmountBiggerThanTotalRemainingAmount('Payment Amount can not bigger than Total Remaining Amount');
        }

        if ($amount < $installmentCost) {
            throw new PaymentAmountSmallerThanZero('Payment amount can not smaller than installment cost');
        }

        if ($amount <= 0) {
            throw new PaymentAmountSmallerThanZero('Payment amount can not smaller than zero');
        }
    }

    public function payInstallment(Model $installment, AssetPerson $applianceOwner, Transaction $transaction): void {
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

    private function processPaymentForDevice(string $deviceSerial, Transaction $transaction, AssetPerson $applianceDetail): void {
        $device = $this->deviceService->getBySerialNumber($deviceSerial);

        if (!$device) {
            throw new ModelNotFoundException("No device found with $deviceSerial");
        }

        $manufacturer = $device->device->manufacturer;
        $installments = $applianceDetail->rates;
        // Use this because we do not want to get down payment as installment
        $secondInstallment = $applianceDetail->rates[1];
        $installmentCost = $secondInstallment ? $secondInstallment['rate_cost']
            : 0;
        $dayDiff = $this->getDayDifferenceBetweenTwoInstallments($installments);
        $transactionData = TransactionDataContainer::initialize($transaction);
        $transactionData->installmentCost = $installmentCost;
        $transactionData->dayDifferenceBetweenTwoInstallments = $dayDiff;
        $transactionData->appliancePerson = $applianceDetail;
        $manufacturerApi = resolve($manufacturer->api_name);
        $transactionData->applianceInstallmentsFullFilled = $this->applianceInstallmentsFullFilled;

        $tokenData = $manufacturerApi->chargeDevice($transactionData);
        $token = Token::query()->make($tokenData);
        $token->transaction()->associate($transactionData->transaction);
        $token->save();
    }

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

            return $dueDateSecondRow->diffInDays($dueDateThirdRow);
        } catch (\Exception $e) {
            return 30;
        }
    }

    public function setPaymentAmount(float $amount): void {
        $this->paymentAmount = $amount;
    }
}

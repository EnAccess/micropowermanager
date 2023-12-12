<?php

namespace App\Services;

use App\Exceptions\PaymentAmountBiggerThanTotalRemainingAmount;
use App\Exceptions\PaymentAmountSmallerThanZero;
use App\Misc\TransactionDataContainer;
use App\Models\AssetRate;
use App\Models\MainSettings;
use App\Models\Token;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use MPM\Device\DeviceService;

class AppliancePaymentService
{
    private float $paymentAmount;

    public function __construct(
        private CashTransactionService $cashTransactionService,
        private MainSettings $mainSettings,
        private AppliancePersonService $appliancePersonService,
        private DeviceService $deviceService
    ) {
    }

    public function getPaymentForAppliance($request, $appliancePerson)
    {
        $creatorId = auth('api')->user()->id;
        $this->paymentAmount = $amount = (double)$request->input('amount');
        $applianceDetail = $this->appliancePersonService->getApplianceDetails($appliancePerson->id);
        $this->validateAmount($applianceDetail, $amount);
        $deviceSerial = $applianceDetail->device_serial;
        $applianceOwner = $appliancePerson->person;
        $ownerAddress = $applianceOwner->addresses()->where('is_primary', 1)->first();
        $sender = $ownerAddress == null ? '-' : $ownerAddress->phone;
        $transaction =
            $this->cashTransactionService->createCashTransaction($creatorId, $amount, $sender, $deviceSerial);
        if ($applianceDetail->device_serial) {
            $this->processPaymentForDevice($deviceSerial, $transaction, $applianceDetail);
        } else {
            $this->createPaymentLog($appliancePerson, $amount, $creatorId);
        }
        $applianceDetail->rates->map(fn($installment) => $this->payInstallment(
            $installment,
            $applianceOwner,
            $transaction
        ));

        return $appliancePerson;
    }

    public function updateRateRemaining($id, $amount): AssetRate
    {
        /** @var AssetRate $applianceRate */
        $applianceRate = AssetRate::query()->findOrFail($id);
        $applianceRate->remaining -= $amount;
        $applianceRate->update();
        $applianceRate->save();
        return $applianceRate;
    }

    public function createPaymentLog($appliancePerson, $amount, $creatorId): void
    {
        /** @var MainSettings $mainSettings */
        $mainSettings = $this->mainSettings->newQuery()->first();
        $currency = $mainSettings  ? $mainSettings->currency : '€';
        event(
            'new.log',
            [
                'logData' => [
                    'user_id' => $creatorId,
                    'affected' => $appliancePerson,
                    'action' => $amount . ' ' . $currency . ' of payment is made '
                ]
            ]
        );
    }

    public function createPaymentHistory($amount, $buyer, $applianceRate, $transaction)
    {
        event(
            'payment.successful',
            [
                'amount' => $amount,
                'paymentService' => 'web',
                'paymentType' => 'installment',
                'sender' => $transaction->sender,
                'paidFor' => $applianceRate,
                'payer' => $buyer,
                'transaction' => $transaction,
            ]
        );
    }

    private function validateAmount($applianceDetail, $amount)
    {
        $totalRemainingAmount = $applianceDetail->rates->sum('remaining');
        $installmentCost = $applianceDetail->rates[1]['rate_cost'];

        if ($amount > $totalRemainingAmount) {
            throw new PaymentAmountBiggerThanTotalRemainingAmount(
                'Payment Amount can not bigger than Total Remaining Amount'
            );
        }

        if ($amount < $installmentCost) {
            throw new PaymentAmountSmallerThanZero(
                'Payment amount can not smaller than installment cost'
            );
        }

        if ($amount <= 0) {
            throw new PaymentAmountSmallerThanZero(
                'Payment amount can not smaller than zero'
            );
        }
    }

    public function payInstallment($installment, $applianceOwner, $transaction)
    {
        if ($installment['remaining'] > 0 && $this->paymentAmount > 0) {
            if ($installment['remaining'] <= $this->paymentAmount) {
                $this->paymentAmount -= $installment['remaining'];
                $applianceRate = $this->updateRateRemaining($installment['id'], $installment['remaining']);
                $this->createPaymentHistory($installment['remaining'], $applianceOwner, $applianceRate, $transaction);
            } else {
                $applianceRate = $this->updateRateRemaining($installment['id'], $this->paymentAmount);
                $this->createPaymentHistory($this->paymentAmount, $applianceOwner, $applianceRate, $transaction);
                $this->paymentAmount = 0;
            }
        }
    }

    private function processPaymentForDevice($deviceSerial, $transaction, $applianceDetail)
    {
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
        $tokenData = $manufacturerApi->chargeMeter($transactionData);
        $token = Token::query()->make([
            'token' => $tokenData['token'],
            'load' => $tokenData['load']
        ]);
        $token->transaction()->associate($transactionData->transaction);
        $token->save();
    }

    public function getDayDifferenceBetweenTwoInstallments($installments)
    {
        try {
            $secondInstallment = $installments[1];
            $thirdInstallment = $installments[2];
            $dueDateSecondRow = Carbon::parse($secondInstallment->due_date);
            $dueDateThirdRow = Carbon::parse($thirdInstallment->due_date);

            return $dueDateSecondRow->diffInDays($dueDateThirdRow);
        } catch (\Exception $e) {
            return 30;
        }
    }

    public function setPaymentAmount($amount): void
    {
        $this->paymentAmount = $amount;
    }
}

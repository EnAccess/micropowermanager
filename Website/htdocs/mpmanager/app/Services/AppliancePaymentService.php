<?php

namespace App\Services;

use App\Exceptions\PaymentAmountBiggerThanTotalRemainingAmount;
use App\Exceptions\PaymentAmountSmallerThanZero;
use App\Exceptions\TransactionAmountNotEnoughException;
use App\Misc\TransactionDataContainer;
use App\Models\AssetRate;
use App\Models\AssetType;
use App\Models\MainSettings;
use App\Models\Meter\MeterToken;
use App\Models\Person\Person;
use App\Models\Transaction\CashTransaction;
use App\Models\Transaction\Transaction;
use App\Traits\ScheduledPluginCommand;

class AppliancePaymentService
{
    use ScheduledPluginCommand;

    const SUN_KING_PLUGIN_ID = 13;
    private $cashTransactionService;
    private $applianceType;
    private $person;
    private $payment;
    private $mainSettings;
    private $appliancePersonService;

    public function __construct(
        CashTransactionService $cashTransactionService,
        AssetType $applianceType,
        Person $person,
        MainSettings $mainSettings,
        AppliancePersonService $appliancePersonService
    ) {
        $this->cashTransactionService = $cashTransactionService;
        $this->applianceType = $applianceType;
        $this->person = $person;
        $this->mainSettings = $mainSettings;
        $this->appliancePersonService = $appliancePersonService;
    }

    public function getPaymentForAppliance($request, $appliancePerson)
    {
        $creatorId = auth('api')->user()->id;
        $this->payment = (int)$request->input('amount');
        $soldApplianceDetail = $this->appliancePersonService->getApplianceDetails($appliancePerson->id);
        if ($this->payment > $soldApplianceDetail->totalRemainingAmount) {
            throw new PaymentAmountBiggerThanTotalRemainingAmount(
                'Payment Amount can not bigger than Total Remaining Amount'
            );
        }
        if ($this->payment <= 0) {
            throw new PaymentAmountSmallerThanZero(
                'Payment amount can not smaller than zero'
            );
        }
        $rates = Collect($soldApplianceDetail->rates);
        $buyer = $appliancePerson->person;
        $appliance = $appliancePerson->asset;
        $buyerAddress = $buyer->addresses()->where('is_primary', 1)->first();
        $sender = $buyerAddress == null ? '-' : $buyerAddress->phone;
        $transaction = $this->cashTransactionService->createCashTransaction($creatorId, $this->payment, $sender);

        try {
            if ($this->isApplianceSHS($appliance) && $this->isSunKingPluginActive()) {
                $this->processSunKingToken($buyer, $transaction, $appliancePerson);
            } else {
                $this->createPaymentLog($appliancePerson, (int)$request->input('amount'), $creatorId);
            }
        } catch (\Exception $e) {
            $cashTransaction = CashTransaction::latest()->first();
            $cashTransaction->transaction()->delete();
            $cashTransaction->delete();
            throw new \Exception($e->getMessage());
        }

        $rates->map(function ($rate) use ($buyer, $appliance, $transaction) {
            if ($rate['remaining'] > 0 && $this->payment > 0) {
                if ($rate['remaining'] <= $this->payment) {
                    $this->payment -= $rate['remaining'];
                    $applianceRate = $this->updateRateRemaining($rate['id'], $rate['remaining']);
                    $this->createPaymentHistory($rate['remaining'], $buyer, $applianceRate, $transaction);
                } else {
                    $applianceRate = $this->updateRateRemaining($rate['id'], $this->payment);
                    $this->createPaymentHistory($this->payment, $buyer, $applianceRate, $transaction);
                    $this->payment = 0;
                }
            }
        });
    }

    public function updateRateRemaining($id, $amount)
    {
        $applianceRate = AssetRate::find($id);
        $applianceRate->remaining -= $amount;
        $applianceRate->update();
        $applianceRate->save();
        return $applianceRate;
    }

    public function createPaymentLog($appliancePerson, $amount, $creatorId)
    {
        $mainSettings = $this->mainSettings->newQuery()->first();
        $currency = $mainSettings === null ? '€' : $mainSettings->currency;
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
                'paymentType' => 'loan rate',
                'sender' => $transaction->sender,
                'paidFor' => $applianceRate,
                'payer' => $buyer,
                'transaction' => $transaction,
            ]
        );
    }

    public function isApplianceSHS($appliance)
    {
        return $appliance->assetType()->first()->id === AssetType::APPLIANCE_TYPE_SHS;
    }

    public function isSunKingPluginActive()
    {
        return $this->checkForPluginStatusIsActive(self::SUN_KING_PLUGIN_ID);
    }

    private function processSunKingToken($buyer, $transaction, $appliancePerson)
    {

        $meter = $buyer->meters()->whereHas(
            'meter',
            static function ($q) {
                $q->whereHas(
                    'manufacturer',
                    static function ($q) {
                        $q->where('name', 'SunKing SHS');
                    }
                );
            }
        )->firstOrFail();

        $transaction->message = $meter->meter->serial_number;
        $transaction->save();

        $transactionData = TransactionDataContainer::initialize($transaction);
        $transactionData->shsLoan = $appliancePerson;
        $tariff = $transactionData->tariff;
        $minimumPurchaseAmount = $tariff->minimum_purchase_amount;

        if ($minimumPurchaseAmount > 0 && $transactionData->transaction->amount < $minimumPurchaseAmount) {
            throw new TransactionAmountNotEnoughException("Minimum purchase amount is {$minimumPurchaseAmount}");
        }

        $api = resolve($transactionData->manufacturer->api_name);
        $tokenData = $api->chargeMeter($transactionData);
        $token = MeterToken::query()->make(
            [
                'token' => $tokenData['token'],
                'energy' => $tokenData['energy'],
            ]
        );
        $token->transaction()->associate($transactionData->transaction);
        $token->meter()->associate($transactionData->meter);
        //save token
        $token->save();

    }
}

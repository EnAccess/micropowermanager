<?php

namespace MPM\CustomBulkRegistration\Ecosys\Services;

use App\Models\AssetPerson;
use App\Models\AssetRate;
use App\Models\MainSettings;
use App\Models\Meter\MeterParameter;
use App\Models\Meter\MeterTariff;
use App\Models\Person\Person;
use App\Models\Transaction\CashTransaction;
use App\Models\Transaction\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use MPM\CustomBulkRegistration\Abstract\CreatorService;

class AppliancePersonService extends CreatorService
{

    private $newTarifName = null;
    private $price = null;
    private $minimumAmount = null;
    private $meterParameterId = null;
    private $personId = null;
    private $downPayment = null;
    private $applianceId = null;
    private $totalPaid = null;
    private $secondPaymentAmount = null;
    private $createdAt = null;
    private $lastPaymentDate = null;

    public function __construct(AssetPerson $appliancePerson)
    {
        parent::__construct($appliancePerson);
    }

    public function resolveCsvDataFromComingRow($csvData)
    {
        $creatorId = User::query()->first()->id;
        $appliancePersonConfig = [
            'asset_id' => 'asset_id',
            'person_id' => 'person_id',
            'total_cost' => 'price',
            'down_payment' => 'down_payment',
            'paid' => 'paid',
            'minimum_payment_amount' => 'minimum_payment_amount',
            'created_at' => 'created_at',
            'last_payment_date' => 'last_payment_date'
        ];

        $appliancePersonData = [
            'asset_id' => $csvData[$appliancePersonConfig['asset_id']],
            'person_id' => $csvData[$appliancePersonConfig['person_id']],
            'total_cost' => $csvData[$appliancePersonConfig['total_cost']],
            'down_payment' => $csvData[$appliancePersonConfig['down_payment']],
            'rate_count' => $this->calculateRateCount(
                $csvData[$appliancePersonConfig['total_cost']],
                $csvData[$appliancePersonConfig['down_payment']],
                $csvData[$appliancePersonConfig['minimum_payment_amount']]),
            'first_payment_date' => $csvData[$appliancePersonConfig['created_at']],
            'creator_id' => $creatorId,
            'creator_type' => 'admin'
        ];

        $this->newTarifName = $csvData['appliance_name'] . '-' . $csvData['serial_number'];
        $this->price = floor((intval($csvData[$appliancePersonConfig['minimum_payment_amount']]) / 7) * 4);
        $this->minimumAmount = $csvData[$appliancePersonConfig['minimum_payment_amount']];
        $this->meterParameterId = $csvData['meter_parameter_id'];
        $this->personId = $csvData['person_id'];
        $this->downPayment = $csvData[$appliancePersonConfig['down_payment']];
        $this->applianceId = $csvData[$appliancePersonConfig['asset_id']];
        $this->totalPaid = $csvData[$appliancePersonConfig['paid']];
        $this->createdAt = $csvData[$appliancePersonConfig['created_at']];
        $this->lastPaymentDate = $csvData[$appliancePersonConfig['last_payment_date']];
        return $this->createRelatedDataIfDoesNotExists($appliancePersonData);
    }

    private function calculateRateCount($price, $downPayment, $minimumPaymentAmount)
    {
        $rawCost = $price - $downPayment;
        $rateCount = 0;

        while ($rawCost > 0) {
            $rawCost = $rawCost - $minimumPaymentAmount;
            $rateCount++;
        }
        return $rateCount;
    }

    public function createRelatedDataIfDoesNotExists($appliancePersonData)
    {
        $creatorId = User::query()->first()->id;
        $currency = MainSettings::query()->first() ? MainSettings::query()->first()->currency : 'MTn';
        $newTariffData = [
            'name' => $this->newTarifName,
            'price' => $this->price,
            'currency' => $currency,
            'factor' => 2,
            'minimum_purchase_amount' => $this->minimumAmount,
        ];
        $newTariff = MeterTariff::query()->firstOrCreate($newTariffData, $newTariffData);
        $meterParameter = MeterParameter::query()->where('id', $this->meterParameterId)->first();
        $meterParameter->tariff_id = $newTariff->id;
        $meterParameter->save();
        $appliancePerson = AssetPerson::query()->firstOrCreate($appliancePersonData, $appliancePersonData);

        $person = Person::query()->where('id', $this->personId)->first();
        $buyerAddress = $person->addresses()->where('is_primary', 1)->first();
        $sender = $buyerAddress == null ? '-' : $buyerAddress->phone;
        $meterParameter = $person->meters()->whereHas(
            'meter',
            static function ($q) {
                $q->whereHas(
                    'manufacturer',
                    static function ($q) {
                        $q->where('name', 'SunKing SHS');
                    }
                );
            }
        )->first();
        $meter = $meterParameter?->meter;

        $cashTransaction = CashTransaction::query()->create(
            [
                'user_id' => $creatorId,
                'status' => 1,
                'created_at' => $this->createdAt,
                'updated_at' => $this->createdAt
            ]
        );
        $transaction = Transaction::query()->make(
            [
                'amount' => $this->downPayment,
                'sender' => $sender,
                'message' => $meter === null ? '-' : $meter->serial_number,
                'type' => 'deferred_payment',
                'created_at' => $this->createdAt,
                'updated_at' => $this->createdAt
            ]
        );
        $transaction->originalTransaction()->associate($cashTransaction);
        $transaction->save();
        $appliance = $appliancePerson->asset;
        $type = $appliance->assetType()->first();
        $soldApplianceDataContainer = app()->makeWith(
            'App\Misc\SoldApplianceDataContainer',
            [
                'asset' => $appliancePerson->asset,
                'assetType' => $type,
                'assetPerson' => $appliancePerson,
                'transaction' => $transaction
            ]
        );
        event('appliance.sold', $soldApplianceDataContainer);
        event(
            'new.log',
            [
                'logData' => [
                    'user_id' => $creatorId,
                    'affected' => $appliancePerson,
                    'action' => $this->downPayment . ' ' . $currency .
                        ' of payment is made as down payment. (Migrated payment from Angaza)'
                ]
            ]
        );
        $this->secondPaymentAmount = $this->totalPaid - $this->downPayment;

        if ($this->secondPaymentAmount > 0) {
            $secondCashTransaction = CashTransaction::query()->create(
                [
                    'user_id' => $creatorId,
                    'status' => 1,
                    'created_at'=> $this->lastPaymentDate,
                    'updated_at'=> $this->lastPaymentDate
                ]
            );
            $secondTransaction = Transaction::query()->make(
                [
                    'amount' => $this->secondPaymentAmount,
                    'sender' => $sender,
                    'message' => $meter === null ? '-' : $meter->serial_number,
                    'type' => 'deferred_payment',
                    'created_at' => $this->lastPaymentDate,
                    'updated_at' => $this->lastPaymentDate
                ]
            );
            $secondTransaction->originalTransaction()->associate($secondCashTransaction);
            $secondTransaction->save();

            event(
                'new.log',
                [
                    'logData' => [
                        'user_id' => $creatorId,
                        'affected' => $appliancePerson,
                        'action' => $this->secondPaymentAmount . ' ' . $currency .
                            ' of payment is made. (Migrated payment from Angaza)'
                    ]
                ]
            );
            $appliancePerson->rates->map(function ($rate) use ($person, $secondTransaction) {
                if ($rate['remaining'] > 0 && $this->secondPaymentAmount > 0) {
                    if ($rate['remaining'] <= $this->secondPaymentAmount) {
                        $this->secondPaymentAmount -= $rate['remaining'];
                        $applianceRate = $this->updateRateRemaining($rate['id'], $rate['remaining']);
                        $this->createPaymentHistory($rate['remaining'], $person, $applianceRate, $secondTransaction);
                    } else {
                        $applianceRate = $this->updateRateRemaining($rate['id'], $this->secondPaymentAmount);
                        $this->createPaymentHistory($this->secondPaymentAmount, $person, $applianceRate, $secondTransaction);
                        $this->secondPaymentAmount = 0;
                    }
                }
            });
        }
        return $appliancePerson;
    }

    private function updateRateRemaining($id, $amount)
    {
        $applianceRate = AssetRate::find($id);
        $applianceRate->remaining -= $amount;
        $applianceRate->update();
        $applianceRate->save();
        return $applianceRate;
    }

    private function createPaymentHistory($amount, $buyer, $applianceRate, $transaction)
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
}
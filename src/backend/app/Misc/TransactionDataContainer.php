<?php

namespace App\Misc;

use App\Models\AssetPerson;
use App\Models\Device;
use App\Models\Manufacturer;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterTariff;
use App\Models\Token;
use App\Models\Transaction\Transaction;
use App\Services\AppliancePaymentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use MPM\Device\DeviceService;

class TransactionDataContainer {
    public int $accessRateDebt;
    public Transaction $transaction;
    public Device $device;
    public ?MeterTariff $tariff;
    public Manufacturer $manufacturer;
    public Token $token;
    public array $paidRates;
    public float $chargedEnergy;
    public float $amount;
    public float $totalAmount;
    public float $rawAmount;
    public ?AssetPerson $appliancePerson;
    public ?Meter $meter;
    public float $installmentCost;
    public string $dayDifferenceBetweenTwoInstallments;
    public bool $applianceInstallmentsFullFilled;

    public static function initialize(Transaction $transaction): TransactionDataContainer {
        /** @var TransactionDataContainer $container */
        $container = app()->make(TransactionDataContainer::class);
        /** @var DeviceService $deviceService */
        $deviceService = app()->make(DeviceService::class);
        $container->chargedEnergy = 0;
        $container->transaction = $transaction;
        $container->totalAmount = $transaction->amount;
        $container->amount = $transaction->amount;
        $container->rawAmount = $transaction->amount;
        $container->applianceInstallmentsFullFilled = false;

        try {
            $container->device = $deviceService->getBySerialNumber($transaction->message);
            $container->tariff = null;
            $container->manufacturer = $container->device->device->manufacturer ?? null;

            // if ($container->device->device instanceof Meter) {
            if (is_a($container->device->device, Meter::class)) {
                /** @var Meter $meter */
                $meter = $container->device->device;
                $container->manufacturer = $meter->manufacturer()->first();
                $container->tariff = $meter->tariff()->first();
                $container->meter = $meter;
            }

            $container->appliancePerson = $transaction->appliance()->first();

            if ($container->appliancePerson) {
                $installments = $container->appliancePerson->rates;
                $appliancePaymentService = app()->make(AppliancePaymentService::class);
                $secondInstallment = $installments[1];
                $installmentCost = $secondInstallment ? $secondInstallment['rate_cost'] : 0;
                $container->installmentCost = $installmentCost;
                $container->dayDifferenceBetweenTwoInstallments =
                    $appliancePaymentService->getDayDifferenceBetweenTwoInstallments($installments);
            }
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Unexpected error occurred while processing transaction. '.$e->getMessage());
        }

        return $container;
    }
}

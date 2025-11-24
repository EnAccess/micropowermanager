<?php

namespace App\DTO;

use App\Models\AssetPerson;
use App\Models\Device;
use App\Models\Manufacturer;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterTariff;
use App\Models\SolarHomeSystem;
use App\Models\Token;
use App\Models\Transaction\Transaction;
use App\Services\AppliancePaymentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use MPM\Device\DeviceService;

class TransactionDataContainer {
    public int $accessRateDebt;
    public Transaction $transaction;
    public Device $device;
    public ?MeterTariff $tariff = null;
    public Manufacturer $manufacturer;
    public Token $token;
    /** @var array<int, array<string, float|int>> */
    public array $paidRates;
    public float $chargedEnergy;
    public float $amount;
    public float $totalAmount;
    public float $rawAmount;
    public ?AssetPerson $appliancePerson = null;
    public ?Meter $meter = null;
    public float $installmentCost = 0;
    public float $dayDifferenceBetweenTwoInstallments;
    public bool $applianceInstallmentsFullFilled;

    public static function initialize(Transaction $transaction): TransactionDataContainer {
        $container = app()->make(TransactionDataContainer::class);
        $deviceService = app()->make(DeviceService::class);

        // Initialize base properties
        $container->chargedEnergy = 0;
        $container->transaction = $transaction;
        $container->totalAmount = $transaction->amount;
        $container->amount = $transaction->amount;
        $container->rawAmount = $transaction->amount;
        $container->applianceInstallmentsFullFilled = false;
        $container->tariff = null;
        $container->meter = null;

        try {
            // Get device by serial number
            $container->device = $deviceService->getBySerialNumber($transaction->message);

            // Get the associated device model (Meter or SHS)
            $deviceModel = $container->device->device;
            $container->manufacturer = $deviceModel->manufacturer ?? null;

            // Handle device type specific logic
            if ($deviceModel instanceof Meter) {
                $container->handleMeterDevice($deviceModel);
            } elseif ($deviceModel instanceof SolarHomeSystem) {
                $container->handleSHSDevice($deviceModel);
            }

            // Handle appliance payments if any
            $container->handleAppliancePayments($transaction);
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Unexpected error occurred while processing transaction. '.$e->getMessage(), $e->getCode(), $e);
        }

        return $container;
    }

    /**
     * Handle meter-specific device initialization.
     */
    private function handleMeterDevice(Meter $meter): void {
        $this->manufacturer = $meter->manufacturer()->first();
        $this->tariff = $meter->tariff()->first();
        $this->meter = $meter;
    }

    /**
     * Handle Solar Home System specific device initialization.
     */
    private function handleSHSDevice(SolarHomeSystem $shs): void {
        $this->manufacturer = $shs->manufacturer()->first();
        // Add any SHS-specific initialization here
    }

    /**
     * Handle appliance payment related initialization.
     */
    private function handleAppliancePayments(Transaction $transaction): void {
        $this->appliancePerson = $transaction->appliance()->first();

        if ($this->appliancePerson) {
            $installments = $this->appliancePerson->rates;
            $appliancePaymentService = app()->make(AppliancePaymentService::class);
            $secondInstallment = $installments[1] ?? null;
            $this->installmentCost = $secondInstallment ? $secondInstallment['rate_cost'] : 0;
            $this->dayDifferenceBetweenTwoInstallments =
                $appliancePaymentService->getDayDifferenceBetweenTwoInstallments($installments);
        }
    }
}

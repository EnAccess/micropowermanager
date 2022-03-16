<?php

namespace Tests\Unit\Transaction;

use App\Misc\TransactionDataContainer;
use App\Models\ConnectionGroup;
use App\Models\ConnectionType;
use App\Models\Manufacturer;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterParameter;
use App\PaymentHandler\AccessRate;
use Database\Factories\AccessRateFactory;
use Database\Factories\AccessRatePaymentFactory;
use Database\Factories\ConnectionGroupFactory;
use Database\Factories\ConnectionTypeFactory;
use Database\Factories\ManufacturerFactory;
use Database\Factories\MeterTariffFactory;
use Database\Factories\MeterTypeFactory;
use Database\Factories\PersonFactory;
use Database\Factories\TransactionFactory;
use Database\Factories\VodacomTransactionFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class AccessRateTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private function initApplication(): void
    {
        Bus::fake();
        $manufacturer = ManufacturerFactory::new()->create();
        $meterType = MeterTypeFactory::new()->create();
        $connectionType = ConnectionTypeFactory::new()->create();
        $connectionGroup = ConnectionGroupFactory::new()->create();
        $meter = new Meter();
        $meter->serial_number = '47000268748';
        $meter->meterType()->associate($meterType);
        $meter->manufacturer()->associate($manufacturer);
        $meter->in_use = 1;
        $meter->save();
        $tariff = MeterTariffFactory::new()->create();
        $accessRate = AccessRateFactory::new()->make();
        $tariff->accessRate()->save($accessRate);
        $customer = PersonFactory::new()->create();
        $meterParameter = new MeterParameter();
        $meterParameter->tariff()->associate($tariff);
        $meterParameter->meter()->associate($meter);
        $meterParameter->connectionGroup()->associate($connectionGroup);
        $meterParameter->connectionType()->associate($connectionType);
        $meter->in_use = 1;
        $meter->save();
        $meterParameter->owner()->associate($customer);
        $meterParameter->save();
        $accessRatePayment = AccessRatePaymentFactory::new()->make();
        $accessRatePayment->meter()->associate($meter);
        $accessRatePayment->accessRate()->associate($accessRate);
        $accessRatePayment->save();
    }


    /**
     * @throws \Exception
     */
    public function testWithNoAccessRate(): void
    {
        $this->initApplication();
        $transaction = $this->createFakeTransaction(500);
        $transactionContainer = TransactionDataContainer::initialize($transaction);
        $transactionData = AccessRate::payAccessRate($transactionContainer);
        $this->assertEquals(0, $transactionData->accessRateDebt);

    }

    public function testWithCoverAccessRate(): void
    {
        $this->initApplication();
        $transaction = $this->createFakeTransaction(500);
        $transactionContainer = TransactionDataContainer::initialize($transaction);
        $accessRatePayment = $transactionContainer->meter->accessRatePayment()->first();
        $accessRatePayment->debt = 100;
        $accessRatePayment->save();
        $transactionData = AccessRate::payAccessRate($transactionContainer);
        $accessRateDebt = $transactionData->accessRateDebt;
        $accessRatePayment = $transactionContainer->meter->accessRatePayment()->first();
        $this->assertEquals(100, $accessRateDebt);
        $this->assertEquals(0, $accessRatePayment->debt);
    }

    public function testWithNonCoverAccessRate(): void
    {
        $this->initApplication();
        $transaction = $this->createFakeTransaction(500);
        $transactionContainer = TransactionDataContainer::initialize($transaction);
        $accessRatePayment = $transactionContainer->meter->accessRatePayment()->first();
        $accessRatePayment->debt = 1000;
        $accessRatePayment->save();
        $transactionData = AccessRate::payAccessRate($transactionContainer);
        $accessRateDebt = $transactionData->accessRateDebt;
        $accessRatePayment = $transactionContainer->meter->accessRatePayment()->first();
        $this->assertEquals(500, $accessRateDebt);
        $this->assertEquals(500, $accessRatePayment->debt);
    }

    public function testWithExactlyAccessRateDebt()
    {
        $this->initApplication();
        $transaction = $this->createFakeTransaction(1000);

        $transactionContainer = TransactionDataContainer::initialize($transaction);

        //with 100 access rate
        $accessRatePayment = $transactionContainer->meter->accessRatePayment()->first();
        $accessRatePayment->debt = 1000;
        $accessRatePayment->save();
        $transactionData = AccessRate::payAccessRate($transactionContainer);
        $accessRateDebt = $transactionData->accessRateDebt;
        //get access rate payment again
        $accessRatePayment = $transactionContainer->meter->accessRatePayment()->first();

        $this->assertEquals(1000, $accessRateDebt);
        $this->assertEquals(0, $accessRatePayment->debt);
    }

    private function createFakeTransaction(int $amount)
    {
        $vodacomTransaction = VodacomTransactionFactory::new()->create();
        $transaction = TransactionFactory::new()->make();
        $transaction->amount = $amount;
        $vodacomTransaction->transaction()->save($transaction);
        return $transaction;
    }


}

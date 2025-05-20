<?php

namespace Inensus\SwiftaPaymentProvider\Tests\Unit;

use App\Jobs\EnergyTransactionProcessor;
use App\Jobs\ProcessPayment;
use App\Jobs\TokenProcessor;
use App\Misc\TransactionDataContainer;
use App\Models\Address\Address;
use App\Models\Manufacturer;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterTariff;
use App\Models\Meter\MeterType;
use App\Models\PaymentHistory;
use App\Models\Person\Person;
use App\Models\Token;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Queue;
use Inensus\MesombPaymentProvider\Services\MesomTransactionService;
use Tests\TestCase;

class PaymentProcessTests extends TestCase {
    use RefreshDatabase;

    public function testProcessPaymentStartsEnergyTransactionProcessor() {
        Queue::fake();
        $this->initializeData();
        $transaction = $this->initializeTransaction();
        ProcessPayment::dispatchNow($transaction->id);
        Queue::assertPushed(EnergyTransactionProcessor::class);
    }

    public function testEnergyTransactionProcessorStartsTokenProcessor() {
        Queue::fake();
        $this->initializeData();
        $transaction = $this->initializeTransaction();
        EnergyTransactionProcessor::dispatchNow($transaction);
        Queue::assertPushed(TokenProcessor::class);
    }

    public function testTokenProcessorChargesMeter() {
        Queue::fake();
        $this->initializeData();
        $transaction = $this->initializeTransaction();
        $transactionData = TransactionDataContainer::initialize($transaction);
        TokenProcessor::dispatchNow($transactionData);
        $tokensCount = Token::query()->get()->count();
        $this->assertEquals(1, $tokensCount);
        $mesombPaymentCount = PaymentHistory::query()
            ->where('payment_service', 'mesomb_transaction')
            ->where('payment_type', 'energy')->get()->count();
        $this->assertEquals(1, $mesombPaymentCount);
    }

    private function initializeTransaction() {
        $validData = [
            'pk' => 'ae58a073-2b76-4774-995b-3743d6793d53',
            'type' => 'PAYMENT',
            'amount' => 10,
            'fees' => 0,
            'meter' => '4700005646',
            'b_party' => '237400001019',
            'message' => 'The payment has been successfully done!',
            'service' => 'MTN',
            'ts' => '2021-05-25 07:11:25.974488+00:00',
            'direction' => -1,
        ];
        $mesombTransactionService = App::make(MesomTransactionService::class);
        $mesombTransaction = $mesombTransactionService->assignIncomingDataToMesombTransaction($validData);
        $transaction = $mesombTransactionService->assignIncomingDataToTransaction($validData);

        return $mesombTransactionService->associateMesombTransactionWithTransaction(
            $mesombTransaction,
            $transaction
        );
    }

    private function initializeData() {
        // create person
        Person::factory()->create();
        // create meter-tariff
        MeterTariff::factory()->create();

        // create meter-type
        MeterType::query()->create([
            'online' => 0,
            'phase' => 1,
            'max_current' => 10,
        ]);

        // create calin manufacturer
        Manufacturer::query()->create([
            'name' => 'CALIN',
            'website' => 'http://www.calinmeter.com/',
            'api_name' => 'CalinApi',
        ]);
        // create meter
        $meter = Meter::query()->create([
            'serial_number' => '4700005646',
            'meter_type_id' => 1,
            'in_use' => 1,
            'manufacturer_id' => 1,
        ]);

        // associate meter with a person
        $p = Person::query()->first();

        // associate address with a person
        $address = Address::query()->make([
            'phone' => '237400001019',
            'is_primary' => 1,
            'owner_type' => 'person',
        ]);
        $address->owner()->associate($p);
        $address->save();
    }
}

<?php

namespace Inensus\MesombPaymentProvider\Tests\Feature;

use App\Jobs\ProcessPayment;
use App\Models\Address\Address;
use App\Models\Manufacturer;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterTariff;
use App\Models\Meter\MeterType;
use App\Models\Person\Person;
use App\Models\Transaction\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Inensus\MesombPaymentProvider\Models\MesombTransaction;
use Tests\CreatesApplication;
use Tests\TestCase;

class PaymentTests extends TestCase {
    use RefreshDatabase;
    use CreatesApplication;

    public function testOnlySuccessesPaymentsCanBeProcessed() {
        $data = [
            'status' => 'FAILED',
            'type' => 'Payment',
            'amount' => 10.0,
            'b_party' => '237400001021',
            'message' => 'The payment has failed!',
        ];
        $response = $this->post('/api/mesomb', $data)->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'code' => 400,
                'title' => 'Mesomp Status Failed.',
                'detail' => $data['message'],
            ],
        ]);
    }

    public function testOnlyOnePhoneNumberIsValidForOneCustomer() {
        $data = [
            'status' => 'SUCCESS',
            'type' => 'Payment',
            'amount' => 10.0,
            'b_party' => '237400001019',
            'message' => 'The payment has been successfully done!',
        ];
        $response = $this->post('/api/mesomb', $data)->assertStatus(422);

        $response->assertJson([
            'errors' => [
                'code' => 422,
                'title' => 'Mesomp Payment Failed.',
                'detail' => 'Each payer must have if and only if registered phone number. Registered phone count with '.$data['b_party'].'is '. 0,
            ],
        ]);
    }

    public function testOnlyOneConnectingMeterIsValidForOneNumber() {
        // create person
        Person::factory()->create();
        // associate meter with a person
        $p = Person::query()->first();
        // associate address with a person
        $address = Address::query()->make([
            'phone' => '+237400001019',
            'is_primary' => 1,
            'owner_type' => 'person',
        ]);
        $address->owner()->associate($p);
        $address->save();
        $data = [
            'status' => 'SUCCESS',
            'type' => 'Payment',
            'amount' => 10.0,
            'b_party' => '237400001019',
            'message' => 'The payment has been successfully done!',
        ];

        $response = $this->post('/api/mesomb', $data)->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'code' => 422,
                'title' => 'Mesomp Payment Failed.',
                'detail' => 'Each payer must have if and only if connected meter with one phone number. Registered meter count is '.Meter::query()->get()->count(),
            ],
        ]);
    }

    public function testValidTransactionStartsTransactionProcessing() {
        Queue::fake();
        $this->withoutExceptionHandling();
        $this->initializeData();

        $data = [
            'pk' => 'ae58a073-2b76-4774-995b-3743d6793d53',
            'status' => 'SUCCESS',
            'type' => 'PAYMENT',
            'amount' => 10,
            'fees' => 0,
            'b_party' => '237400001019',
            'message' => 'The payment has been successfully done!',
            'service' => 'MTN',
            'ts' => '2021-05-25 07:11:25.974488+00:00',
            'direction' => -1,
        ];
        $response = $this->post('/api/mesomb', $data);
        $mesombTransactions = MesombTransaction::query()->get();
        $mesombTransaction = $mesombTransactions->first();
        $transactions = Transaction::query()->get();
        $response->assertJson([
            'data' => [
                'type' => 'mesomb_transaction',
                'pk' => $mesombTransaction->pk,
                'attributes' => [
                    'status' => $mesombTransaction->status,
                    'type' => $mesombTransaction->type,
                    'amount' => $mesombTransaction->amount,
                    'fees' => $mesombTransaction->fees,
                    'b_party' => $mesombTransaction->b_party,
                    'message' => $mesombTransaction->message,
                ],
            ],
        ]);
        $this->assertEquals(1, $transactions->count());
        $this->assertEquals(1, $mesombTransactions->count());
        $this->assertEquals(0, $mesombTransaction->status);
        Queue::assertPushed(ProcessPayment::class);
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
            'name' => 'Spark Meters',
            'website' => 'https://www.sparkmeter.io/',
            'api_name' => 'SparkMeterApi',
        ]);
        // create meter
        $meter = Meter::query()->create([
            'serial_number' => '4700005646',
            'meter_type_id' => 1,
            'in_use' => 1,
            'manufacturer_id' => 1,
            'connection_type_id' => 1,
            'connection_group_id' => 1,
        ]);

        // associate meter with a person
        $p = Person::query()->first();
        $meter->device()->create([
            'owner_type' => 'person',
            'owner_id' => $p->id,
        ]);
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

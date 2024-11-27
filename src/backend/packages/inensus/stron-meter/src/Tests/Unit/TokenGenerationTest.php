<?php

namespace Inensus\StronMeter\Tests\Unit;

use App\Jobs\TokenProcessor;
use App\Misc\TransactionDataContainer;
use App\Models\Address\Address;
use App\Models\MainSettings;
use App\Models\Manufacturer;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterTariff;
use App\Models\Meter\MeterType;
use App\Models\Person\Person;
use App\Models\Transaction\Transaction;
use App\Models\Transaction\VodacomTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Inensus\StronMeter\Models\StronCredential;
use Tests\TestCase;

class TokenGenerationTest extends TestCase {
    use RefreshDatabase;

    /** @test */
    public function isTokenGenerated() {
        Queue::fake();
        Config::set('app.debug', false);
        $transaction = $this->initializeData();
        $transactionContainer = TransactionDataContainer::initialize($transaction);
        $transactionContainer->chargedEnergy = 1;

        TokenProcessor::dispatchNow(
            $transactionContainer
        );
        Queue::assertPushed(TokenProcessor::class);
    }

    private function initializeData() {
        // create person
        factory(MainSettings::class)->create();

        // create person
        factory(Person::class)->create();

        // create meter-tariff
        factory(MeterTariff::class)->create();

        // create meter-type
        MeterType::query()->create([
            'online' => 0,
            'phase' => 1,
            'max_current' => 10,
        ]);

        // create calin manufacturer
        Manufacturer::query()->create([
            'name' => 'Stron Meters',
            'website' => 'http://www.stronsmart.com/',
            'api_name' => 'StronMeterApi',
        ]);
        StronCredential::query()->create([
            'username' => 'Admin',
            'password' => '123456',
            'is_authenticated' => 1,
            'company_name' => 'intergas',
            'api_url' => 'http://www.saitecapi.stronpower.com/api',
            'api_token' => '53b16e3d9622ea3bccc49d88ba6e4e75',
        ]);
        // create meter
        Meter::query()->create([
            'serial_number' => '58042000008',
            'meter_type_id' => 1,
            'in_use' => 1,
            'manufacturer_id' => 1,
        ]);

        // associate meter with a person
        $p = Person::query()->first();
        $p->meters()->create([
            'tariff_id' => 1,
            'meter_id' => 1,
            'connection_type_id' => 1,
            'connection_group_id' => 1,
        ]);

        // associate address with a person
        $address = Address::query()->make([
            'phone' => '+905396398161',
            'is_primary' => 1,
            'owner_type' => 'person',
        ]);

        $address->owner()->associate($p);
        $address->save();

        // create transaction
        factory(VodacomTransaction::class)->create();
        $transaction = factory(Transaction::class)->make();
        $transaction->message = '58042000008';

        $vodacomTransaction = VodacomTransaction::query()->first();
        $vodacomTransaction->transaction()->save($transaction);

        return $transaction;
    }
}

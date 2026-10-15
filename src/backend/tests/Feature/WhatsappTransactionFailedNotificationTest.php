<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\TransactionFailedEvent;
use App\Models\Address\Address;
use App\Models\City;
use App\Models\Device;
use App\Models\MpmPlugin;
use App\Models\Plugins;
use App\Models\Sms;
use App\Models\Transaction\CashTransaction;
use App\Models\Transaction\Transaction;
use App\Models\User;
use App\Plugins\WhatsappBusinessApi\Models\WhatsappBusinessOptIn;
use App\Plugins\WhatsappBusinessApi\Models\WhatsappCredential;
use Database\Factories\CityFactory;
use Database\Factories\Person\PersonFactory;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\Http;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class WhatsappTransactionFailedNotificationTest extends TestCase {
    use RefreshMultipleDatabases;

    private User $user;

    protected function setUp(): void {
        parent::setUp();
        $this->user = UserFactory::new()->create(['company_id' => $this->companyId]);

        Plugins::query()->updateOrCreate(
            ['mpm_plugin_id' => MpmPlugin::WHATSAPP_BUSINESS_API],
            ['status' => Plugins::ACTIVE]
        );

        WhatsappCredential::query()->create([
            'access_token' => 'test-token',
            'phone_number_id' => '123456789',
            'waba_id' => '987654321',
            'webhook_secret' => 'test-secret',
            'transaction_confirmation_template' => 'transaction_confirmation',
            'reminder_template' => 'appliance_rate_reminder',
        ]);
    }

    private function createCity(): City {
        $cluster = \Database\Factories\ClusterFactory::new()->create(['manager_id' => $this->user->id]);
        $miniGrid = \Database\Factories\MiniGridFactory::new()->create(['cluster_id' => $cluster->id]);

        return CityFactory::new()->create([
            'country_id' => 1,
            'mini_grid_id' => $miniGrid->id,
        ]);
    }

    public function testSendsWhatsAppNotificationWhenTransactionFailsAndCustomerIsOptedIn(): void {
        Http::fake([
            'https://graph.facebook.com/v18.0/123456789/messages' => Http::response([
                'messaging_product' => 'whatsapp',
                'messages' => [['id' => 'wamid.HBgLMTIzNDU2Nzg5']],
            ], 200),
        ]);

        $city = $this->createCity();
        $person = PersonFactory::new()->create();
        Address::query()->create([
            'owner_type' => 'person',
            'owner_id' => $person->id,
            'phone' => '+255712345678',
            'is_primary' => 1,
            'city_id' => $city->id,
        ]);

        WhatsappBusinessOptIn::query()->create([
            'person_id' => $person->id,
            'opted_in' => true,
        ]);

        $device = Device::query()->create([
            'device_serial' => 'METER-FAIL-001',
            'person_id' => $person->id,
            'device_type' => 'meter',
            'device_id' => 1,
        ]);

        $cash = CashTransaction::query()->create([
            'user_id' => $this->user->id,
            'status' => 0,
        ]);

        $transaction = $cash->transaction()->create([
            'amount' => 50,
            'sender' => '+255712345678',
            'message' => 'METER-FAIL-001',
            'type' => Transaction::TYPE_ENERGY,
        ]);

        event(new TransactionFailedEvent($transaction, 'Token generation failed due to meter communication timeout'));

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), '123456789/messages')
                && $request['messaging_product'] === 'whatsapp'
                && $request['type'] === 'text'
                && str_contains($request['text']['body'], 'could not be processed')
                && str_contains($request['text']['body'], 'Token generation failed');
        });

        $this->assertDatabaseHas('sms', [
            'receiver' => '+255712345678',
            'trigger_type' => Transaction::RELATION_NAME,
            'trigger_id' => $transaction->id,
            'status' => Sms::STATUS_SENT,
        ], 'tenant');
    }

    public function testDoesNotSendNotificationWhenCustomerIsNotOptedIn(): void {
        Http::fake();

        $city = $this->createCity();
        $person = PersonFactory::new()->create();
        Address::query()->create([
            'owner_type' => 'person',
            'owner_id' => $person->id,
            'phone' => '+255712345678',
            'is_primary' => 1,
            'city_id' => $city->id,
        ]);

        WhatsappBusinessOptIn::query()->create([
            'person_id' => $person->id,
            'opted_in' => false,
        ]);

        $device = Device::query()->create([
            'device_serial' => 'METER-FAIL-002',
            'person_id' => $person->id,
            'device_type' => 'meter',
            'device_id' => 2,
        ]);

        $cash = CashTransaction::query()->create([
            'user_id' => $this->user->id,
            'status' => 0,
        ]);

        $transaction = $cash->transaction()->create([
            'amount' => 50,
            'sender' => '+255712345678',
            'message' => 'METER-FAIL-002',
            'type' => Transaction::TYPE_ENERGY,
        ]);

        event(new TransactionFailedEvent($transaction, 'Payment provider rejected charge'));

        Http::assertNothingSent();
    }

    public function testDoesNotSendNotificationWhenPluginIsDisabled(): void {
        Http::fake();

        Plugins::query()->where('mpm_plugin_id', MpmPlugin::WHATSAPP_BUSINESS_API)->update(['status' => Plugins::INACTIVE]);

        $city = $this->createCity();
        $person = PersonFactory::new()->create();
        Address::query()->create([
            'owner_type' => 'person',
            'owner_id' => $person->id,
            'phone' => '+255712345678',
            'is_primary' => 1,
            'city_id' => $city->id,
        ]);

        WhatsappBusinessOptIn::query()->create([
            'person_id' => $person->id,
            'opted_in' => true,
        ]);

        $device = Device::query()->create([
            'device_serial' => 'METER-FAIL-003',
            'person_id' => $person->id,
            'device_type' => 'meter',
            'device_id' => 3,
        ]);

        $cash = CashTransaction::query()->create([
            'user_id' => $this->user->id,
            'status' => 0,
        ]);

        $transaction = $cash->transaction()->create([
            'amount' => 50,
            'sender' => '+255712345678',
            'message' => 'METER-FAIL-003',
            'type' => Transaction::TYPE_ENERGY,
        ]);

        event(new TransactionFailedEvent($transaction, 'Token generation failed'));

        Http::assertNothingSent();
    }
}

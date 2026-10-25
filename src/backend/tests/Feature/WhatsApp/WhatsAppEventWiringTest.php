<?php

namespace Tests\Feature\WhatsApp;

use App\Events\ApplianceRateReminderEvent;
use App\Events\TransactionSuccessfulEvent;
use App\Models\User;
use App\Plugins\WhatsApp\Jobs\SendWhatsAppMessageJob;
use App\Plugins\WhatsApp\Listeners\SendWhatsAppLowBalanceListener;
use App\Plugins\WhatsApp\Listeners\SendWhatsAppTransactionListener;
use App\Plugins\WhatsApp\Models\WhatsAppMessage;
use App\Plugins\WhatsApp\Services\WhatsAppCredentialService;
use Database\Factories\AppliancePersonFactory;
use Database\Factories\ApplianceRateFactory;
use Database\Factories\Person\PersonFactory;
use Database\Factories\TransactionFactory;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WhatsAppEventWiringTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();

        Queue::fake();

        User::query()->firstOrCreate(
            ['email' => 'whatsapp-wiring@example.com'],
            [
                'name' => 'WhatsApp Wiring',
                'password' => bcrypt('password'),
                'company_id' => 1,
            ],
        );

        resolve(WhatsAppCredentialService::class)->updateCredentials([
            'phone_number_id' => '1098234982374982',
            'whatsapp_business_account_id' => '987234982374982',
            'api_access_token' => 'test-valid-access-token',
            'sender_phone_number' => '+250788123456',
            'api_version' => 'v21.0',
            'is_enabled' => true,
        ]);
    }

    public function testDomainEventsAreMappedToWhatsAppListeners(): void {
        Event::fake();

        Event::assertListening(TransactionSuccessfulEvent::class, SendWhatsAppTransactionListener::class);
        Event::assertListening(ApplianceRateReminderEvent::class, SendWhatsAppLowBalanceListener::class);
    }

    public function testSuccessfulTransactionQueuesReceiptAndTokenDelivery(): void {
        $transaction = TransactionFactory::new()->create(['sender' => '+250788123456']);

        event(new TransactionSuccessfulEvent($transaction));

        Queue::assertPushed(SendWhatsAppMessageJob::class, 1);
        $this->assertSame(
            1,
            WhatsAppMessage::query()->where('notification_type', WhatsAppMessage::TYPE_PAYMENT_RECEIPT)->count(),
        );
    }

    public function testApplianceRateReminderQueuesLowBalanceWarning(): void {
        $person = PersonFactory::new()->create();
        $person->addresses()->create([
            'email' => 'reminder@example.com',
            'phone' => '+250788123456',
            'street' => 'Reminder Street',
            'is_primary' => 1,
        ]);
        $appliancePerson = AppliancePersonFactory::new()->create(['person_id' => $person->id]);
        $applianceRate = ApplianceRateFactory::new()->create([
            'appliance_person_id' => $appliancePerson->id,
            'remaining' => 1000,
        ]);

        event(new ApplianceRateReminderEvent($applianceRate, true));

        Queue::assertPushed(SendWhatsAppMessageJob::class, 1);
        $message = WhatsAppMessage::query()->where('notification_type', WhatsAppMessage::TYPE_LOW_BALANCE_WARNING)->first();
        $this->assertInstanceOf(WhatsAppMessage::class, $message);
        $this->assertSame('appliance_rate:'.$applianceRate->id.':overdue', $message->reference);
    }
}

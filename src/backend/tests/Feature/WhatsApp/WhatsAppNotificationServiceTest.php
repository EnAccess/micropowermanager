<?php

namespace Tests\Feature\WhatsApp;

use App\Models\Token;
use App\Models\User;
use App\Plugins\WhatsApp\Jobs\SendWhatsAppMessageJob;
use App\Plugins\WhatsApp\Models\WhatsAppMessage;
use App\Plugins\WhatsApp\Services\WhatsAppCredentialService;
use App\Plugins\WhatsApp\Services\WhatsAppNotificationService;
use Database\Factories\AppliancePersonFactory;
use Database\Factories\ApplianceRateFactory;
use Database\Factories\Person\PersonFactory;
use Database\Factories\TokenFactory;
use Database\Factories\TransactionFactory;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WhatsAppNotificationServiceTest extends TestCase {
    private WhatsAppNotificationService $notificationService;

    protected function setUp(): void {
        parent::setUp();

        Queue::fake();

        // Dispatched WhatsApp jobs resolve the tenant from the first user.
        User::query()->firstOrCreate(
            ['email' => 'whatsapp-test@example.com'],
            [
                'name' => 'WhatsApp Test',
                'password' => bcrypt('password'),
                'company_id' => 1,
            ],
        );

        $this->notificationService = resolve(WhatsAppNotificationService::class);
    }

    public function testItQueuesPaymentReceiptForTransaction(): void {
        $this->enableNotifications();

        $transaction = TransactionFactory::new()->create([
            'amount' => 5000,
            'sender' => '+250788123456',
        ]);

        $message = $this->notificationService->sendPaymentReceipt($transaction);

        $this->assertInstanceOf(WhatsAppMessage::class, $message);
        $this->assertSame(WhatsAppMessage::TYPE_PAYMENT_RECEIPT, $message->notification_type);
        $this->assertSame(WhatsAppMessage::STATUS_QUEUED, $message->status);
        $this->assertSame('250788123456', $message->recipient_phone);
        $this->assertStringContainsString('5000.00', (string) $message->body);
        $this->assertStringContainsString((string) $transaction->id, (string) $message->body);
        Queue::assertPushed(SendWhatsAppMessageJob::class, 1);
    }

    public function testItDeliversTokenGeneratedByMpmWithoutRegenerating(): void {
        $this->enableNotifications();

        $transaction = TransactionFactory::new()->create(['sender' => '+250788123456']);
        TokenFactory::new()->create([
            'transaction_id' => $transaction->id,
            'token' => '123456789012',
        ]);

        $message = $this->notificationService->sendTokenDelivery($transaction->fresh());

        $this->assertInstanceOf(WhatsAppMessage::class, $message);
        $this->assertSame(WhatsAppMessage::TYPE_TOKEN_DELIVERY, $message->notification_type);
        $this->assertStringContainsString('123456789012', (string) $message->body);
        $this->assertSame(1, Token::query()->where('transaction_id', $transaction->id)->count());
    }

    public function testItSkipsTokenDeliveryWhenNoTokenExists(): void {
        $this->enableNotifications();

        $transaction = TransactionFactory::new()->create(['sender' => '+250788123456']);

        $message = $this->notificationService->sendTokenDelivery($transaction);

        $this->assertNull($message);
        $this->assertSame(0, WhatsAppMessage::query()->count());
        Queue::assertNotPushed(SendWhatsAppMessageJob::class);
    }

    public function testItQueuesLowBalanceWarningFromExistingThresholdData(): void {
        $this->enableNotifications();

        $person = PersonFactory::new()->create(['name' => 'Amara', 'surname' => 'Banda']);
        $person->addresses()->create([
            'email' => 'amara@example.com',
            'phone' => '+250788123456',
            'street' => 'Test Street',
            'is_primary' => 1,
        ]);
        $appliancePerson = AppliancePersonFactory::new()->create(['person_id' => $person->id]);
        $applianceRate = ApplianceRateFactory::new()->create([
            'appliance_person_id' => $appliancePerson->id,
            'remaining' => 2500,
            'due_date' => '2026-11-01',
        ]);

        $message = $this->notificationService->sendLowBalanceWarning($applianceRate, false);

        $this->assertInstanceOf(WhatsAppMessage::class, $message);
        $this->assertSame(WhatsAppMessage::TYPE_LOW_BALANCE_WARNING, $message->notification_type);
        $this->assertSame($person->id, $message->person_id);
        $this->assertStringContainsString('2500.00', (string) $message->body);
        $this->assertStringContainsString('2026-11-01', (string) $message->body);
        $this->assertSame('appliance_rate:'.$applianceRate->id.':upcoming', $message->reference);
    }

    public function testItDoesNothingWhenPluginIsDisabled(): void {
        $this->configureCredentials(['is_enabled' => false]);

        $transaction = TransactionFactory::new()->create(['sender' => '+250788123456']);

        $this->assertNull($this->notificationService->sendPaymentReceipt($transaction));
        $this->assertSame(0, WhatsAppMessage::query()->count());
    }

    public function testItRespectsNotificationTypePreference(): void {
        $this->enableNotifications(['payment_receipts' => false]);

        $transaction = TransactionFactory::new()->create(['sender' => '+250788123456']);

        $this->assertNull($this->notificationService->sendPaymentReceipt($transaction));
        $this->assertSame(0, WhatsAppMessage::query()->count());
    }

    public function testItRecordsFailureWhenCustomerHasNoValidPhoneNumber(): void {
        $this->enableNotifications();

        $transaction = TransactionFactory::new()->create(['sender' => '-']);

        $message = $this->notificationService->sendPaymentReceipt($transaction);

        $this->assertNull($message);
        $failed = WhatsAppMessage::query()
            ->where('notification_type', WhatsAppMessage::TYPE_PAYMENT_RECEIPT)
            ->first();
        $this->assertInstanceOf(WhatsAppMessage::class, $failed);
        $this->assertSame(WhatsAppMessage::STATUS_FAILED, $failed->status);
        $this->assertNotNull($failed->error_message);
        Queue::assertNotPushed(SendWhatsAppMessageJob::class);
    }

    public function testItDoesNotQueueDuplicateForSameTransaction(): void {
        $this->enableNotifications();

        $transaction = TransactionFactory::new()->create(['sender' => '+250788123456']);

        $first = $this->notificationService->sendPaymentReceipt($transaction);
        $second = $this->notificationService->sendPaymentReceipt($transaction);

        $this->assertInstanceOf(WhatsAppMessage::class, $first);
        $this->assertNull($second);
        $this->assertSame(1, WhatsAppMessage::query()->where('notification_type', WhatsAppMessage::TYPE_PAYMENT_RECEIPT)->count());
        Queue::assertPushed(SendWhatsAppMessageJob::class, 1);
    }

    /**
     * @param array<string, bool>|null $notificationSettings
     */
    private function enableNotifications(?array $notificationSettings = null): void {
        $this->configureCredentials([
            'is_enabled' => true,
            'notification_settings' => $notificationSettings,
        ]);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function configureCredentials(array $overrides = []): void {
        resolve(WhatsAppCredentialService::class)->updateCredentials([
            'phone_number_id' => '1098234982374982',
            'whatsapp_business_account_id' => '987234982374982',
            'api_access_token' => 'test-valid-access-token',
            'sender_phone_number' => '+250788123456',
            'api_version' => 'v21.0',
            ...$overrides,
        ]);
    }
}

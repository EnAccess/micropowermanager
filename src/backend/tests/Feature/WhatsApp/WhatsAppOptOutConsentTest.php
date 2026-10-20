<?php

namespace Tests\Feature\WhatsApp;

use App\Events\TransactionSuccessfulEvent;
use App\Models\MainSettings;
use App\Models\Sms;
use App\Models\SmsBody;
use App\Models\Token;
use App\Models\Transaction\Transaction;
use App\Models\User;
use App\Plugins\WhatsApp\Jobs\SendWhatsAppMessageJob;
use App\Plugins\WhatsApp\Models\WhatsAppMessage;
use App\Plugins\WhatsApp\Models\WhatsAppOptOut;
use App\Plugins\WhatsApp\Services\WhatsAppCredentialService;
use App\Plugins\WhatsApp\Services\WhatsAppOptOutService;
use Database\Factories\MainSettingsFactory;
use Database\Factories\TokenFactory;
use Database\Factories\TransactionFactory;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WhatsAppOptOutConsentTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();

        Queue::fake();

        User::query()->firstOrCreate(
            ['email' => 'whatsapp-optout@example.com'],
            [
                'name' => 'WhatsApp OptOut Test',
                'password' => bcrypt('password'),
                'company_id' => 1,
            ],
        );

        SmsBody::query()->updateOrCreate(
            ['reference' => 'TokenConfirmationMeter'],
            [
                'title' => 'TokenConfirmationMeter',
                'body' => 'Dear [name] [surname], your token is [token].',
                'place_holder' => '',
                'variables' => '',
            ],
        );

        $mainSettings = MainSettings::query()->first();
        if ($mainSettings instanceof MainSettings) {
            $mainSettings->update(['transaction_sms_enabled' => true]);
        } else {
            MainSettingsFactory::new()->create(['transaction_sms_enabled' => true]);
        }
    }

    public function testOptedOutCustomerReceivesSmsDirectlyInsteadOfWhatsApp(): void {
        $this->enableWhatsApp();
        $phone = '+250788123456';

        // Customer opts out of WhatsApp
        $optOutService = resolve(WhatsAppOptOutService::class);
        $optOutService->optOut($phone, 'customer_request');

        $this->assertTrue($optOutService->isOptedOut($phone));

        // Simulate customer energy payment
        $transaction = $this->transactionWithToken($phone);
        event(new TransactionSuccessfulEvent($transaction));

        // WhatsApp is NOT dispatched because customer opted out
        Queue::assertNotPushed(SendWhatsAppMessageJob::class);

        // Native SMS is dispatched directly
        $sms = Sms::query()->latest('id')->first();
        $this->assertInstanceOf(Sms::class, $sms);
        $this->assertStringContainsString('999888777666', $sms->body);
    }

    public function testInboundStopKeywordOptsOutCustomer(): void {
        $phone = '+250799888777';
        $optOutService = resolve(WhatsAppOptOutService::class);

        $reply = $optOutService->handleInboundKeyword($phone, 'STOP');
        $this->assertNotNull($reply);
        $this->assertStringContainsString('unsubscribed', $reply);
        $this->assertTrue($optOutService->isOptedOut($phone));

        // Re-subscribe with START
        $optInReply = $optOutService->handleInboundKeyword($phone, 'START');
        $this->assertNotNull($optInReply);
        $this->assertStringContainsString('subscribed', $optInReply);
        $this->assertFalse($optOutService->isOptedOut($phone));
    }

    private function transactionWithToken(string $phone = '+250788123456'): Transaction {
        $transaction = TransactionFactory::new()->create(['sender' => $phone]);
        TokenFactory::new()->create([
            'transaction_id' => $transaction->id,
            'token' => '999888777666',
        ]);

        return $transaction;
    }

    private function enableWhatsApp(): void {
        resolve(WhatsAppCredentialService::class)->updateCredentials([
            'phone_number_id' => '1098234982374982',
            'whatsapp_business_account_id' => '987234982374982',
            'api_access_token' => 'test-valid-access-token',
            'sender_phone_number' => '+250788123456',
            'api_version' => 'v21.0',
            'is_enabled' => true,
        ]);
    }
}

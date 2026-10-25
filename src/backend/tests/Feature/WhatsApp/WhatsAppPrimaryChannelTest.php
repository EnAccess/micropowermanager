<?php

namespace Tests\Feature\WhatsApp;

use App\Events\TransactionSuccessfulEvent;
use App\Jobs\SmsProcessor;
use App\Models\MainSettings;
use App\Models\Sms;
use App\Models\SmsBody;
use App\Models\Transaction\Transaction;
use App\Models\User;
use App\Plugins\WhatsApp\Jobs\SendWhatsAppMessageJob;
use App\Plugins\WhatsApp\Services\WhatsAppCredentialService;
use Database\Factories\MainSettingsFactory;
use Database\Factories\TokenFactory;
use Database\Factories\TransactionFactory;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WhatsAppPrimaryChannelTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();

        Queue::fake();

        // Dispatched WhatsApp jobs resolve the tenant from the first user.
        User::query()->firstOrCreate(
            ['email' => 'whatsapp-primary@example.com'],
            [
                'name' => 'WhatsApp Primary',
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

    public function testTransactionConfirmationSmsIsSkippedWhileWhatsAppIsPrimary(): void {
        $this->enableWhatsApp();
        $transaction = $this->transactionWithToken();

        event(new TransactionSuccessfulEvent($transaction));

        $this->assertSame(0, Sms::query()->count());
        Queue::assertNotPushed(SmsProcessor::class);
        Queue::assertPushed(SendWhatsAppMessageJob::class);
    }

    public function testTransactionConfirmationSmsIsSentWhileWhatsAppIsDisabled(): void {
        $transaction = $this->transactionWithToken();

        event(new TransactionSuccessfulEvent($transaction));

        $sms = Sms::query()->first();
        $this->assertInstanceOf(Sms::class, $sms);
        $this->assertStringContainsString('999888777666', $sms->body);
    }

    private function transactionWithToken(): Transaction {
        $transaction = TransactionFactory::new()->create(['sender' => '+250788123456']);
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

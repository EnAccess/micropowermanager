<?php

namespace Tests\Feature\WhatsApp;

use App\Jobs\SmsProcessor;
use App\Models\Sms;
use App\Models\SmsBody;
use App\Models\Token;
use App\Models\Transaction\Transaction;
use App\Models\User;
use App\Plugins\WhatsApp\Models\WhatsAppMessage;
use App\Plugins\WhatsApp\Services\WhatsAppSmsFallbackService;
use Database\Factories\AppliancePersonFactory;
use Database\Factories\ApplianceRateFactory;
use Database\Factories\Person\PersonFactory;
use Database\Factories\TokenFactory;
use Database\Factories\TransactionFactory;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WhatsAppSmsFallbackTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();

        Queue::fake();

        // Dispatched SMS jobs resolve the tenant from the first user.
        User::query()->firstOrCreate(
            ['email' => 'whatsapp-fallback@example.com'],
            [
                'name' => 'WhatsApp Fallback',
                'password' => bcrypt('password'),
                'company_id' => 1,
            ],
        );

        $smsBodies = [
            'TokenConfirmationMeter' => 'Dear [name] [surname], your token is [token].',
            'SmsReminderHeader' => 'Dear [name] [surname],',
            'OverdueApplianceRateReminder' => 'OVERDUE RATE: pay [remaining].',
            'SmsReminderFooter' => 'Your Company.',
        ];

        foreach ($smsBodies as $reference => $body) {
            SmsBody::query()->updateOrCreate(
                ['reference' => $reference],
                ['title' => $reference, 'body' => $body, 'place_holder' => '', 'variables' => ''],
            );
        }
    }

    public function testItSendsTokenConfirmationSmsWhenWhatsAppDeliveryFails(): void {
        $transaction = $this->transactionWithToken();
        $message = $this->whatsAppMessage([
            'notification_type' => WhatsAppMessage::TYPE_TOKEN_DELIVERY,
            'transaction_id' => $transaction->id,
            'reference' => 'transaction:'.$transaction->id,
        ]);

        $result = resolve(WhatsAppSmsFallbackService::class)->sendPending();

        $this->assertSame(1, $result['sent']);
        $sms = Sms::query()->latest('id')->first();
        $this->assertInstanceOf(Sms::class, $sms);
        $this->assertStringContainsString('123456789012', $sms->body);
        $this->assertSame($transaction->getMorphClass(), $sms->trigger_type);
        $this->assertSame($transaction->id, $sms->trigger_id);

        $message->refresh();
        $this->assertNotNull($message->fallback_at);
        $this->assertSame(WhatsAppMessage::FALLBACK_REASON_DELIVERY_FAILED, $message->fallback_reason);
        $this->assertSame($sms->id, $message->fallback_sms_id);

        // The fallback reuses the token already persisted on the transaction.
        $this->assertSame(1, Token::query()->where('transaction_id', $transaction->id)->count());
        $this->assertSame('123456789012', $transaction->fresh()->token->token);
        Queue::assertPushed(SmsProcessor::class);
    }

    public function testItFallsBackWhenDeliveryConfirmationTimesOut(): void {
        $transaction = $this->transactionWithToken();
        $message = $this->whatsAppMessage([
            'notification_type' => WhatsAppMessage::TYPE_TOKEN_DELIVERY,
            'transaction_id' => $transaction->id,
            'reference' => 'transaction:'.$transaction->id,
            'status' => WhatsAppMessage::STATUS_SENT,
            'sent_at' => now()->subMinutes(6),
            'created_at' => now()->subMinutes(6),
        ]);

        $result = resolve(WhatsAppSmsFallbackService::class)->sendPending();

        $this->assertSame(1, $result['sent']);
        $this->assertSame(1, Sms::query()->count());
        $this->assertSame(WhatsAppMessage::FALLBACK_REASON_DELIVERY_TIMEOUT, $message->refresh()->fallback_reason);
    }

    public function testItWaitsForTheDeliveryTimeoutBeforeFallingBack(): void {
        $transaction = $this->transactionWithToken();
        $message = $this->whatsAppMessage([
            'notification_type' => WhatsAppMessage::TYPE_TOKEN_DELIVERY,
            'transaction_id' => $transaction->id,
            'reference' => 'transaction:'.$transaction->id,
            'status' => WhatsAppMessage::STATUS_SENT,
            'sent_at' => now()->subMinutes(4),
            'created_at' => now()->subMinutes(4),
        ]);

        $result = resolve(WhatsAppSmsFallbackService::class)->sendPending();

        $this->assertSame(0, $result['sent']);
        $this->assertSame(0, Sms::query()->count());
        $this->assertNull($message->refresh()->fallback_at);
    }

    public function testItDoesNotFallBackForDeliveredMessages(): void {
        $transaction = $this->transactionWithToken();
        $message = $this->whatsAppMessage([
            'notification_type' => WhatsAppMessage::TYPE_TOKEN_DELIVERY,
            'transaction_id' => $transaction->id,
            'reference' => 'transaction:'.$transaction->id,
            'status' => WhatsAppMessage::STATUS_DELIVERED,
            'sent_at' => now()->subMinutes(10),
            'delivered_at' => now()->subMinutes(9),
            'created_at' => now()->subMinutes(10),
        ]);

        $result = resolve(WhatsAppSmsFallbackService::class)->sendPending();

        $this->assertSame(0, $result['sent']);
        $this->assertSame(0, Sms::query()->count());
        $this->assertNull($message->refresh()->fallback_at);
    }

    public function testItDoesNotDuplicateAnExistingTransactionSms(): void {
        $transaction = $this->transactionWithToken();
        $existing = $transaction->sms()->create([
            'receiver' => '+250788123456',
            'body' => 'Confirmation sent through the regular SMS path.',
            'status' => Sms::STATUS_STORED,
            'uuid' => 'existing-sms-uuid',
            'sender_id' => null,
            'direction' => Sms::DIRECTION_OUTGOING,
        ]);
        $message = $this->whatsAppMessage([
            'notification_type' => WhatsAppMessage::TYPE_TOKEN_DELIVERY,
            'transaction_id' => $transaction->id,
            'reference' => 'transaction:'.$transaction->id,
        ]);

        $result = resolve(WhatsAppSmsFallbackService::class)->sendPending();

        $this->assertSame(0, $result['sent']);
        $this->assertSame(1, Sms::query()->count());
        $message->refresh();
        $this->assertSame(WhatsAppMessage::FALLBACK_REASON_EXISTING_SMS, $message->fallback_reason);
        $this->assertSame($existing->id, $message->fallback_sms_id);
    }

    public function testItSendsOverdueReminderWhenLowBalanceWarningFails(): void {
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
            'remaining' => 4000,
            'due_date' => '2026-10-01',
        ]);
        $message = $this->whatsAppMessage([
            'notification_type' => WhatsAppMessage::TYPE_LOW_BALANCE_WARNING,
            'reference' => 'appliance_rate:'.$applianceRate->id.':overdue',
            'body' => 'Undelivered balance warning',
        ]);

        $result = resolve(WhatsAppSmsFallbackService::class)->sendPending();

        $this->assertSame(1, $result['sent']);
        $sms = Sms::query()->latest('id')->first();
        $this->assertInstanceOf(Sms::class, $sms);
        $this->assertStringContainsString('OVERDUE RATE', $sms->body);
        $this->assertSame($applianceRate->getMorphClass(), $sms->trigger_type);
        $this->assertSame($applianceRate->id, $sms->trigger_id);
    }

    public function testItIgnoresNonTransactionalMessages(): void {
        $this->whatsAppMessage(['notification_type' => WhatsAppMessage::TYPE_TEST]);

        $result = resolve(WhatsAppSmsFallbackService::class)->sendPending();

        $this->assertSame(0, $result['sent']);
        $this->assertSame(0, Sms::query()->count());
    }

    public function testItGivesUpAfterMaxAttempts(): void {
        $transaction = $this->transactionWithToken();
        $message = $this->whatsAppMessage([
            'notification_type' => WhatsAppMessage::TYPE_TOKEN_DELIVERY,
            'transaction_id' => $transaction->id,
            'reference' => 'transaction:'.$transaction->id,
            'fallback_attempts' => 3,
        ]);

        $result = resolve(WhatsAppSmsFallbackService::class)->sendPending();

        $this->assertSame(0, $result['sent']);
        $this->assertSame(0, Sms::query()->count());
        $this->assertNull($message->refresh()->fallback_at);
    }

    public function testItSendsEachFallbackOnlyOnce(): void {
        $transaction = $this->transactionWithToken();
        $this->whatsAppMessage([
            'notification_type' => WhatsAppMessage::TYPE_TOKEN_DELIVERY,
            'transaction_id' => $transaction->id,
            'reference' => 'transaction:'.$transaction->id,
        ]);

        $fallback = resolve(WhatsAppSmsFallbackService::class);
        $fallback->sendPending();
        $second = $fallback->sendPending();

        $this->assertSame(0, $second['sent']);
        $this->assertSame(1, Sms::query()->count());
    }

    private function transactionWithToken(): Transaction {
        $transaction = TransactionFactory::new()->create(['sender' => '+250788123456']);
        TokenFactory::new()->create([
            'transaction_id' => $transaction->id,
            'token' => '123456789012',
            'token_type' => Token::TYPE_ENERGY,
        ]);

        return $transaction;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function whatsAppMessage(array $attributes): WhatsAppMessage {
        return WhatsAppMessage::query()->create(array_merge([
            'notification_type' => WhatsAppMessage::TYPE_TOKEN_DELIVERY,
            'recipient_phone' => '250788123456',
            'body' => 'Notification body',
            'status' => WhatsAppMessage::STATUS_FAILED,
            'error_message' => 'Provider rejected the message.',
        ], $attributes));
    }
}

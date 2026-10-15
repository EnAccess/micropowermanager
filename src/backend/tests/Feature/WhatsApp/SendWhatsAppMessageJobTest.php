<?php

namespace Tests\Feature\WhatsApp;

use App\Plugins\WhatsApp\Exceptions\WhatsAppApiException;
use App\Plugins\WhatsApp\Jobs\SendWhatsAppMessageJob;
use App\Plugins\WhatsApp\Models\WhatsAppMessage;
use App\Plugins\WhatsApp\Services\WhatsAppCredentialService;
use App\Plugins\WhatsApp\Services\WhatsAppMessageService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SendWhatsAppMessageJobTest extends TestCase {
    private WhatsAppMessageService $messageService;

    protected function setUp(): void {
        parent::setUp();

        resolve(WhatsAppCredentialService::class)->updateCredentials([
            'phone_number_id' => '1098234982374982',
            'whatsapp_business_account_id' => '987234982374982',
            'api_access_token' => 'test-valid-access-token',
            'sender_phone_number' => '+250788123456',
            'api_version' => 'v21.0',
            'is_enabled' => true,
        ]);

        $this->messageService = resolve(WhatsAppMessageService::class);
    }

    public function testItDeliversQueuedMessageAndRecordsProviderId(): void {
        $message = $this->queueMessage();

        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.JOB123']],
            ], 200),
        ]);

        new SendWhatsAppMessageJob($message->id, 1)->handle();

        $message->refresh();
        $this->assertSame(WhatsAppMessage::STATUS_SENT, $message->status);
        $this->assertSame('wamid.JOB123', $message->message_id);
        $this->assertNotNull($message->sent_at);
    }

    public function testItRecordsFailureWithoutThrowingWhenProviderRejectsTheMessage(): void {
        $message = $this->queueMessage();

        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'error' => ['message' => 'Recipient not in allowed list'],
            ], 400),
        ]);

        new SendWhatsAppMessageJob($message->id, 1)->handle();

        $message->refresh();
        $this->assertSame(WhatsAppMessage::STATUS_FAILED, $message->status);
        $this->assertStringContainsString('Recipient not in allowed list', (string) $message->error_message);
    }

    public function testDeliverIsIdempotentForAlreadySentMessage(): void {
        $message = $this->queueMessage();

        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.FIRST']],
            ], 200),
        ]);

        $this->messageService->deliver($message);
        $this->messageService->deliver($message->fresh());

        Http::assertSentCount(1);
    }

    public function testFailedMessageDoesNotBlockRetry(): void {
        // A failed message must not block a later retry of the same notification.
        $message = $this->queueMessage(WhatsAppMessage::STATUS_FAILED);

        $this->assertFalse($this->messageService->hasActiveMessage($message->notification_type, (string) $message->reference));
    }

    public function testDeliverThrowsWhenCredentialsMissing(): void {
        $message = $this->queueMessage();

        resolve(WhatsAppCredentialService::class)->updateCredentials([
            'phone_number_id' => null,
            'api_access_token' => null,
            'is_enabled' => false,
        ]);

        $this->expectException(WhatsAppApiException::class);

        try {
            $this->messageService->deliver($message);
        } finally {
            $message->refresh();
            $this->assertSame(WhatsAppMessage::STATUS_FAILED, $message->status);
        }
    }

    private function queueMessage(string $status = WhatsAppMessage::STATUS_QUEUED): WhatsAppMessage {
        $message = $this->messageService->queue([
            'notification_type' => WhatsAppMessage::TYPE_PAYMENT_RECEIPT,
            'reference' => 'transaction:123',
            'recipient_phone' => '250788123456',
            'body' => 'Your payment was received.',
        ]);

        if ($status !== WhatsAppMessage::STATUS_QUEUED) {
            $message->update(['status' => $status]);
            $message->refresh();
        }

        return $message;
    }
}

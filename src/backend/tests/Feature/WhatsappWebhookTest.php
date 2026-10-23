<?php

namespace Tests\Feature;

use App\Models\Sms;
use App\Plugins\Whatsapp\Models\WhatsappCredential;
use App\Plugins\Whatsapp\Models\WhatsappMessage;
use App\Plugins\Whatsapp\Models\WhatsappOptIn;
use App\Plugins\Whatsapp\Services\WhatsappBotService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class WhatsappWebhookTest extends TestCase {
    private const string SECRET = 'app-secret';
    private const string VERIFY_TOKEN = 'verify-me';

    protected function setUp(): void {
        parent::setUp();

        WhatsappCredential::query()->create([
            'phone_number_id' => '123456789',
            'access_token' => Crypt::encryptString('test-token'),
            'app_secret' => Crypt::encryptString(self::SECRET),
            'webhook_verify_token' => self::VERIFY_TOKEN,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return TestResponse<Response>
     */
    private function signedPost(array $payload, ?string $signature = null): TestResponse {
        $body = json_encode($payload);
        $signature ??= 'sha256='.hash_hmac('sha256', $body, self::SECRET);

        return $this->call('POST', '/api/whatsapp/webhook/1', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => $signature,
        ], $body);
    }

    /**
     * @param array<int, array<string, mixed>> $statuses
     *
     * @return array<string, mixed>
     */
    private function statusPayload(array $statuses): array {
        return ['entry' => [['changes' => [['value' => ['statuses' => $statuses]]]]]];
    }

    public function testVerifyHandshakeEchoesTheChallenge(): void {
        $response = $this->get('/api/whatsapp/webhook/1?hub.mode=subscribe&hub.verify_token='.self::VERIFY_TOKEN.'&hub.challenge=98765');

        $response->assertOk();
        $this->assertSame('98765', $response->getContent());
    }

    public function testVerifyHandshakeRejectsAWrongToken(): void {
        $this->get('/api/whatsapp/webhook/1?hub.mode=subscribe&hub.verify_token=wrong&hub.challenge=98765')
            ->assertForbidden();
    }

    public function testPostWithAnInvalidSignatureIsRejected(): void {
        $this->signedPost($this->statusPayload([]), 'sha256=deadbeef')->assertForbidden();
    }

    public function testPostWithAValidSignatureIsAccepted(): void {
        $this->signedPost($this->statusPayload([]))->assertOk();
    }

    public function testDeliveryReceiptsOnlyMoveTheStatusForward(): void {
        $sms = Sms::query()->create([
            'receiver' => '+250700000001',
            'body' => 'x',
            'direction' => Sms::DIRECTION_OUTGOING,
            'status' => Sms::STATUS_SENT,
        ]);
        $message = WhatsappMessage::query()->create([
            'sms_id' => $sms->id,
            'message_id' => 'wamid.RCPT',
            'status' => 'sent',
        ]);

        $this->signedPost($this->statusPayload([['id' => 'wamid.RCPT', 'status' => 'read']]))->assertOk();
        $this->assertSame('read', $message->refresh()->status);

        // a late "delivered" must not move it back from "read"
        $this->signedPost($this->statusPayload([['id' => 'wamid.RCPT', 'status' => 'delivered']]))->assertOk();
        $this->assertSame('read', $message->refresh()->status);
        $this->assertSame(Sms::STATUS_DELIVERED, $sms->refresh()->status);
    }

    public function testFailedReceiptRecordsTheMetaReason(): void {
        $sms = Sms::query()->create([
            'receiver' => '+250700000001',
            'body' => 'x',
            'direction' => Sms::DIRECTION_OUTGOING,
            'status' => Sms::STATUS_SENT,
        ]);
        $message = WhatsappMessage::query()->create([
            'sms_id' => $sms->id,
            'message_id' => 'wamid.FAILED',
            'status' => 'sent',
        ]);

        $this->signedPost($this->statusPayload([[
            'id' => 'wamid.FAILED',
            'status' => 'failed',
            'errors' => [['code' => 131049, 'title' => 'Healthy ecosystem engagement']],
        ]]))->assertOk();

        $this->assertSame('failed', $message->refresh()->status);
        $this->assertStringContainsString('131049', $message->error);
        $sms->refresh();
        $this->assertSame(Sms::STATUS_FAILED, $sms->status);
        $this->assertStringContainsString('WhatsApp:', $sms->error_message);
    }

    /**
     * @return array<string, mixed>
     */
    private function inbound(string $id, string $text): array {
        return ['id' => $id, 'from' => '250700000001', 'type' => 'text', 'text' => ['body' => $text]];
    }

    public function testUnknownTextRepliesWithTheMenu(): void {
        Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.R']]], 200)]);

        resolve(WhatsappBotService::class)->handleInbound($this->inbound('wamid.IN1', 'hi'));

        Http::assertSent(fn (Request $request) => $request['type'] === 'text'
            && $request['to'] === '250700000001'
            && str_contains($request['text']['body'], 'Reply with a number'));
    }

    public function testTheSameInboundMessageIsOnlyProcessedOnce(): void {
        Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.R']]], 200)]);
        $bot = resolve(WhatsappBotService::class);

        $bot->handleInbound($this->inbound('wamid.DUP', 'hi'));
        $bot->handleInbound($this->inbound('wamid.DUP', 'hi'));

        Http::assertSentCount(1);
    }

    public function testCustomerCanOptInAndOutByMessage(): void {
        Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.R']]], 200)]);
        $bot = resolve(WhatsappBotService::class);

        $bot->handleInbound($this->inbound('wamid.IN2', '2'));
        $optIn = WhatsappOptIn::query()->where('phone', '250700000001')->firstOrFail();
        $this->assertNotNull($optIn->opted_in_at);
        $this->assertNull($optIn->revoked_at);
        $this->assertSame('inbound_keyword', $optIn->source);

        $bot->handleInbound($this->inbound('wamid.IN3', 'STOP'));
        $this->assertNotNull($optIn->refresh()->revoked_at);
    }
}

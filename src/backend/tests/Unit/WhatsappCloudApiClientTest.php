<?php

namespace Tests\Unit;

use App\Plugins\WhatsappMessaging\Exceptions\WhatsappMessageNotSentException;
use App\Plugins\WhatsappMessaging\Models\WhatsappCredential;
use App\Plugins\WhatsappMessaging\Services\WhatsappCloudApiClient;
use App\Plugins\WhatsappMessaging\Services\WhatsappCredentialService;
use App\Plugins\WhatsappMessaging\Services\WhatsappOutboundMessage;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

class WhatsappCloudApiClientTest extends TestCase {
    use MockeryPHPUnitIntegration;

    private const string MESSAGES_URL = 'https://graph.facebook.com/v26.0/1234567890/messages';
    private const string WAMID = 'wamid.HBgMMjU1NzEyMzQ1Njc4FQIAERgSQ0E';

    public function testItSendsATextMessagePayload(): void {
        Http::fake([self::MESSAGES_URL => Http::response($this->successResponse())]);

        $this->client()->sendMessage(new WhatsappOutboundMessage('+255712345678', 'Payment received'));

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === self::MESSAGES_URL
            && $request->hasHeader('Authorization', 'Bearer test-access-token')
            && $request->data() === [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => '255712345678',
                'type' => 'text',
                'text' => ['preview_url' => false, 'body' => 'Payment received'],
            ]);
    }

    public function testItSendsATemplateMessagePayload(): void {
        Http::fake([self::MESSAGES_URL => Http::response($this->successResponse())]);

        $this->client()->sendMessage(new WhatsappOutboundMessage(
            '+255712345678',
            'Payment received from Amina: 5000',
            'payment_receipt',
            'en',
            ['Amina', '5000'],
        ));

        Http::assertSent(fn (Request $request): bool => $request->data() === [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => '255712345678',
            'type' => 'template',
            'template' => [
                'name' => 'payment_receipt',
                'language' => ['code' => 'en'],
                'components' => [[
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => 'Amina'],
                        ['type' => 'text', 'text' => '5000'],
                    ],
                ]],
            ],
        ]);
    }

    public function testItReturnsTheWamidOfAnAcceptedMessage(): void {
        Http::fake([self::MESSAGES_URL => Http::response($this->successResponse())]);

        $wamid = $this->client()->sendMessage(new WhatsappOutboundMessage('+255712345678', 'Payment received'));

        $this->assertSame(self::WAMID, $wamid);
    }

    public function testItParsesTheCustomerServiceWindowError(): void {
        Http::fake([self::MESSAGES_URL => Http::response($this->errorResponse(131047, 'Re-engagement message', 'Message failed to send because more than 24 hours have passed since the customer last replied to this number.'), 400)]);

        $exception = $this->captureSendFailure();

        $this->assertSame(131047, $exception->metaErrorCode);
        $this->assertSame('Re-engagement message', $exception->metaErrorMessage);
        $this->assertStringContainsString('more than 24 hours', (string) $exception->metaErrorDetails);
        $this->assertStringContainsString('A template message is required', $exception->getMessage());
        $this->assertFalse($exception->isRetryable());
    }

    public function testItParsesTheExpiredAccessTokenError(): void {
        Http::fake([self::MESSAGES_URL => Http::response($this->errorResponse(190, 'Error validating access token: Session has expired'), 401)]);

        $exception = $this->captureSendFailure();

        $this->assertSame(190, $exception->metaErrorCode);
        $this->assertStringContainsString('Session has expired', $exception->getMessage());
        $this->assertStringContainsString('Update it in the WhatsApp plugin settings', $exception->getMessage());
        $this->assertFalse($exception->isRetryable());
    }

    public function testThroughputLimitAndServerErrorsAreRetryable(): void {
        Http::fakeSequence(self::MESSAGES_URL)
            ->push($this->errorResponse(130429, 'Rate limit hit'), 400)
            ->push($this->errorResponse(1, 'An unknown error occurred'), 500);

        $this->assertTrue($this->captureSendFailure()->isRetryable());
        $this->assertTrue($this->captureSendFailure()->isRetryable());
    }

    public function testConnectionFailuresAreRetryable(): void {
        Http::fake([self::MESSAGES_URL => Http::failedConnection()]);

        $this->assertTrue($this->captureSendFailure()->isRetryable());
    }

    public function testItRejectsAGraphApiVersionThatIsNotAVersionString(): void {
        Http::fake();

        $exception = $this->captureSendFailure($this->credential('v26.0/../evil'));

        $this->assertStringContainsString('Graph API version', $exception->getMessage());
        Http::assertNothingSent();
    }

    public function testItFetchesMessageTemplatesAcrossAllPages(): void {
        Http::fakeSequence('https://graph.facebook.com/v26.0/9876543210/message_templates*')
            ->push(['data' => [['name' => 'first']], 'paging' => ['cursors' => ['after' => 'CURSOR'], 'next' => 'https://graph.facebook.com/next']])
            ->push(['data' => [['name' => 'second']], 'paging' => ['cursors' => ['after' => 'END']]]);

        $templates = $this->client()->getMessageTemplates();

        $this->assertSame([['name' => 'first'], ['name' => 'second']], $templates);
        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request['fields'] === 'name,status,language,category,components'
            && ($request->data()['after'] ?? null) === 'CURSOR');
    }

    private function captureSendFailure(?WhatsappCredential $credential = null): WhatsappMessageNotSentException {
        try {
            $this->client($credential)->sendMessage(new WhatsappOutboundMessage('+255712345678', 'Payment received'));
        } catch (WhatsappMessageNotSentException $exception) {
            return $exception;
        }

        $this->fail('Expected '.WhatsappMessageNotSentException::class.' to be thrown.');
    }

    private function client(?WhatsappCredential $credential = null): WhatsappCloudApiClient {
        $credentialService = \Mockery::mock(WhatsappCredentialService::class);
        $credentialService->shouldReceive('getCredentials')->andReturn($credential ?? $this->credential());

        return new WhatsappCloudApiClient($credentialService);
    }

    private function credential(string $graphApiVersion = 'v26.0'): WhatsappCredential {
        $credential = new WhatsappCredential();
        $credential->access_token = 'test-access-token';
        $credential->phone_number_id = '1234567890';
        $credential->business_account_id = '9876543210';
        $credential->graph_api_version = $graphApiVersion;

        return $credential;
    }

    /**
     * @return array<string, mixed>
     */
    private function successResponse(): array {
        return [
            'messaging_product' => 'whatsapp',
            'contacts' => [['input' => '255712345678', 'wa_id' => '255712345678']],
            'messages' => [['id' => self::WAMID]],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function errorResponse(int $code, string $message, ?string $details = null): array {
        $error = ['message' => $message, 'type' => 'OAuthException', 'code' => $code, 'fbtrace_id' => 'AbCdEf'];

        if ($details !== null) {
            $error['error_data'] = ['details' => $details];
        }

        return ['error' => $error];
    }
}

<?php

namespace Tests\Unit;

use App\Plugins\WhatsappMessaging\Exceptions\WhatsappApiException;
use App\Plugins\WhatsappMessaging\Http\Clients\WhatsappApiClient;
use App\Plugins\WhatsappMessaging\Models\WhatsappCredential;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsappApiClientTest extends TestCase {
    private const string MESSAGES_URL = 'https://graph.facebook.com/v26.0/1234567890/messages';
    private const string WAMID = 'wamid.HBgMMjU1NzEyMzQ1Njc4FQIAERgSQ0E';

    public function testItSendsAMessageToTheWhatsappIdOfTheNumber(): void {
        Http::fake([self::MESSAGES_URL => Http::response(['messages' => [['id' => self::WAMID]]])]);

        $wamid = new WhatsappApiClient()->sendMessage($this->credential(), '+255712345678', ['type' => 'text', 'text' => ['body' => 'Payment received']]);

        $this->assertSame(self::WAMID, $wamid);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->hasHeader('Authorization', 'Bearer test-access-token')
            && $request->data() === ['messaging_product' => 'whatsapp', 'to' => '255712345678', 'type' => 'text', 'text' => ['body' => 'Payment received']]);
    }

    public function testItExplainsAMetaErrorToTheOperator(): void {
        Http::fake([self::MESSAGES_URL => Http::response(['error' => [
            'message' => 'Re-engagement message',
            'code' => 131047,
            'error_data' => ['details' => 'Message failed to send because more than 24 hours have passed since the customer last replied.'],
        ]], 400)]);

        $exception = $this->captureFailure($this->credential());

        $this->assertSame(131047, $exception->getCode());
        $this->assertStringContainsString('more than 24 hours', $exception->getMessage());
        $this->assertStringContainsString('A template message is required', $exception->getMessage());
        $this->assertFalse($exception->isTemporary());
    }

    public function testItReportsAnUnreachableMetaWithoutAMetaCode(): void {
        Http::fake([self::MESSAGES_URL => Http::failedConnection()]);

        $exception = $this->captureFailure($this->credential());

        $this->assertSame(0, $exception->getCode());
        $this->assertStringContainsString('Could not reach the WhatsApp Cloud API', $exception->getMessage());
        $this->assertTrue($exception->isTemporary());
    }

    public function testItTreatsMetaOutagesAndThrottlingAsTemporary(): void {
        Http::fake([self::MESSAGES_URL => Http::sequence()
            ->push(['error' => ['message' => 'Rate limit hit', 'code' => 130429]], 400)
            ->push(['error' => ['message' => 'Service temporarily unavailable', 'code' => 2]], 503)]);

        $this->assertTrue($this->captureFailure($this->credential())->isTemporary());
        $this->assertTrue($this->captureFailure($this->credential())->isTemporary());
    }

    public function testItTreatsIncompleteCredentialsAsPermanent(): void {
        Http::fake();

        $this->assertFalse($this->captureFailure($this->credential('v26.0/../evil'))->isTemporary());
    }

    public function testItRejectsAGraphApiVersionThatIsNotAVersionString(): void {
        Http::fake();

        $this->assertStringContainsString('Graph API version', $this->captureFailure($this->credential('v26.0/../evil'))->getMessage());
        Http::assertNothingSent();
    }

    public function testItSendsGetParametersAsTheQueryString(): void {
        Http::fake(['https://graph.facebook.com/v26.0/9876543210*' => Http::response(['currency' => 'USD'])]);

        $response = new WhatsappApiClient()->get($this->credential(), '9876543210', ['fields' => 'currency']);

        $this->assertSame('USD', $response->json('currency'));
        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET' && $request['fields'] === 'currency');
    }

    private function captureFailure(WhatsappCredential $credential): WhatsappApiException {
        try {
            new WhatsappApiClient()->sendMessage($credential, '+255712345678', ['type' => 'text', 'text' => ['body' => 'Payment received']]);
        } catch (WhatsappApiException $exception) {
            return $exception;
        }

        $this->fail('Expected '.WhatsappApiException::class.' to be thrown.');
    }

    private function credential(string $graphApiVersion = 'v26.0'): WhatsappCredential {
        return new WhatsappCredential(['access_token' => 'test-access-token', 'phone_number_id' => '1234567890', 'business_account_id' => '9876543210', 'graph_api_version' => $graphApiVersion]);
    }
}

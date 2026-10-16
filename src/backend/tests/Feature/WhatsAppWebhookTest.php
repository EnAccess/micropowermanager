<?php

namespace Tests\Feature;

use App\Plugins\WhatsApp\Models\WhatsAppMessage;
use App\Plugins\WhatsApp\Services\WhatsAppDeliveryService;
use App\Services\ApiResolvers\ThirdPartyApiResolverService;
use App\Services\DatabaseProxyManagerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\WhatsAppTestCase;

class WhatsAppWebhookTest extends WhatsAppTestCase {
    protected function setUp(): void {
        parent::setUp();
        $this->seedWebhookCredentials(1);
        $this->seedWebhookCredentials(2);
    }

    public function testVerificationReturnsExactChallengeThroughRealHttpPipeline(): void {
        $this->getJson('/api/whatsapp/webhook/1?hub.mode=subscribe&hub.verify_token=verify-1&hub.challenge=000123%2Babc')
            ->assertOk()->assertContent('000123+abc')->assertHeader('Content-Type', 'text/plain; charset=utf-8');
    }

    public function testVerificationRejectsInvalidOrAmbiguousFieldsBeforeTenantBinding(): void {
        $this->preventTenantBinding();
        foreach ([
            'hub.mode=subscribe&hub.verify_token=wrong&hub.challenge=123',
            'hub.mode=subscribe&hub.challenge=123',
            'hub.mode=unsubscribe&hub.verify_token=verify-1&hub.challenge=123',
            'hub.mode=subscribe&hub.verify_token=verify-1&hub.challenge=',
            'hub.mode=subscribe&hub.verify_token=verify-1&hub.challenge[]=123',
            'hub.mode=subscribe&hub.verify_token=verify-1&hub.challenge=123&hub.challenge=456',
            'hub_mode=subscribe&hub_verify_token=verify-1&hub_challenge=123',
        ] as $query) {
            $this->getJson('/api/whatsapp/webhook/1?'.$query)->assertForbidden();
        }
    }

    public function testUnknownCompanyAndCrossCompanyVerificationAreRejected(): void {
        $this->preventTenantBinding();
        foreach ([99, 2] as $companyId) {
            $this->getJson('/api/whatsapp/webhook/'.$companyId.'?hub.mode=subscribe&hub.verify_token=verify-1&hub.challenge=123')->assertForbidden();
        }
    }

    public function testAuthenticatedDeliveryEventsAreAcknowledgedAfterDurableStaging(): void {
        $this->postSigned($this->payload())->assertOk();
        $this->assertDatabaseCount('whatsapp_delivery_events', 1, 'tenant');
        $this->assertDatabaseCount('whatsapp_messages', 0, 'tenant');
    }

    public function testCompleteStatusBatchIsValidatedBeforeAnyPersistence(): void {
        $payload = $this->payload();
        $payload['entry'][0]['changes'][0]['value']['statuses'][] = ['id' => 'other', 'status' => 'read', 'timestamp' => '9999999999999999999999'];
        $this->postSigned($payload)->assertStatus(400);
        $this->assertDatabaseCount('whatsapp_delivery_events', 0, 'tenant');
        $payload = $this->payload();
        $payload['entry'][] = $this->payload(2)['entry'][0];
        $this->postSigned($payload)->assertForbidden();
        $this->assertDatabaseCount('whatsapp_delivery_events', 0, 'tenant');
    }

    public function testAllValidEventsInOneWebhookUpdateOnlyCorrelatedMessages(): void {
        WhatsAppMessage::query()->create(['message_id' => 'wamid.example', 'recipient_phone' => '+250788123456', 'notification_type' => 'payment_receipt', 'status' => 'sent']);
        $payload = $this->payload();
        $payload['entry'][0]['changes'][0]['value']['statuses'][] = ['id' => 'wamid.example', 'status' => 'read', 'timestamp' => '1700000001'];
        $payload['entry'][0]['changes'][0]['value']['statuses'][] = ['id' => 'wamid.later', 'status' => 'failed', 'timestamp' => '1700000002', 'errors' => [['code' => 131026, 'message' => 'do not persist this text']]];
        $this->postSigned($payload)->assertOk();
        $this->assertSame('read', WhatsAppMessage::query()->first()->status);
        $this->assertDatabaseCount('whatsapp_delivery_events', 3, 'tenant');
        $this->assertSame(1, DB::connection('tenant')->table('whatsapp_delivery_events')->where('state', 'pending')->count());
        Http::assertNothingSent();
    }

    public function testProcessingFailureCannotProduceSuccessfulAcknowledgement(): void {
        $service = \Mockery::mock(WhatsAppDeliveryService::class)->makePartial();
        $service->shouldReceive('normalize')->andReturn([]);
        $service->shouldReceive('accept')->once()->andThrow(new \RuntimeException('sensitive database error'));
        $this->app->instance(WhatsAppDeliveryService::class, $service);
        $response = $this->postSigned($this->payload())->assertStatus(503);
        $this->assertStringNotContainsString('sensitive database error', $response->getContent());
    }

    public function testUnhandledInboundEventsStillCannotBeSilentlyAcknowledged(): void {
        $payload = $this->payload();
        $payload['entry'][0]['changes'][0]['value']['messages'] = [['id' => 'incoming']];
        $this->postSigned($payload)->assertStatus(503);
        $this->assertDatabaseCount('whatsapp_delivery_events', 1, 'tenant');
    }

    public function testUnsupportedAuthenticatedEventsAreSafelyAcknowledged(): void {
        $payload = $this->payload();
        $payload['entry'][0]['changes'] = [['field' => 'message_template_status_update', 'value' => ['event' => 'APPROVED']]];
        $this->postSigned($payload)->assertOk();
        $this->assertDatabaseCount('whatsapp_messages', 0, 'tenant');
    }

    public function testMissingInvalidAndTamperedSignaturesFailBeforeTenantBinding(): void {
        $this->preventTenantBinding();
        foreach (['', 'sha256=bad', 'sha1='.str_repeat('a', 64), 'sha256='.str_repeat('a', 64)] as $signature) {
            $this->call('POST', '/api/whatsapp/webhook/1', server: ['HTTP_ACCEPT' => 'application/json', 'HTTP_X_HUB_SIGNATURE_256' => $signature], content: '{}')->assertForbidden();
        }
        $body = json_encode($this->payload());
        $this->call('POST', '/api/whatsapp/webhook/1', server: [
            'HTTP_ACCEPT' => 'application/json', 'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $body, 'secret-1'),
        ], content: $body.' ')->assertForbidden();
        $this->postSigned($this->payload(), 'secret-2')->assertForbidden();
    }

    public function testOwnershipMismatchesAndMixedBatchesFailBeforeTenantBinding(): void {
        $this->preventTenantBinding();
        $payload = $this->payload();
        $payload['entry'][0]['id'] = 'account-2';
        $this->postSigned($payload)->assertForbidden();
        $payload = $this->payload();
        $payload['entry'][0]['changes'][0]['value']['metadata']['phone_number_id'] = 'phone-2';
        $this->postSigned($payload)->assertForbidden();
        $payload = $this->payload();
        $payload['entry'][] = $this->payload(2)['entry'][0];
        $this->postSigned($payload)->assertForbidden();
    }

    public function testMalformedSignedPayloadsAreRejectedAndValidBatchesAreStaged(): void {
        foreach (['{', 'null', '[]', '{"object":"whatsapp_business_account","entry":[]}'] as $body) {
            $this->postBody($body)->assertStatus(400);
        }
        $payload = $this->payload();
        unset($payload['entry'][0]['changes'][0]['value']['statuses'][0]['timestamp']);
        $this->postSigned($payload)->assertStatus(400);
        $payload = $this->payload();
        $payload['entry'][] = $payload['entry'][0];
        $this->postSigned($payload)->assertOk();
    }

    public function testLimitsAreEnforcedBeforeCredentialLookup(): void {
        config(['whatsapp.webhook_max_bytes' => 2]);
        $this->postSigned($this->payload())->assertStatus(413);
        config(['whatsapp.webhook_max_bytes' => 1048576, 'whatsapp.webhook_requests_per_minute' => 1]);
        $this->postSigned($this->payload())->assertStatus(429);
    }

    public function testRawQueryLimitCannotBeBypassedUsingDuplicateParameters(): void {
        $this->preventTenantBinding();
        $query = 'hub.mode=subscribe&hub.verify_token=verify-1&'.str_repeat('hub.challenge=1&', 600);
        $this->getJson('/api/whatsapp/webhook/1?'.$query)->assertStatus(413);
    }

    public function testProductionCannotBeEnabledAndFeatureDefaultsToDisabled(): void {
        $this->preventTenantBinding();
        config(['whatsapp.webhooks_enabled' => false]);
        $this->postSigned($this->payload())->assertStatus(503);
        config(['whatsapp.webhooks_enabled' => true]);
        $this->app->instance('env', 'production');
        $this->getJson('/api/whatsapp/webhook/1?hub.mode=subscribe&hub.verify_token=verify-1&hub.challenge=123')->assertStatus(503);
        $this->postSigned($this->payload())->assertStatus(503);
    }

    public function testExistingRoutesRemainOutsideWebhookResolverAndRequireJwt(): void {
        $resolver = resolve(ThirdPartyApiResolverService::class);
        foreach (['api/whatsapp/credential', 'api/whatsapp/test-message'] as $path) {
            $this->assertFalse($resolver->matches($path));
        }
        $this->getJson('/api/whatsapp/credential')->assertUnauthorized();
        $this->postJson('/api/whatsapp/test-message', ['recipient_phone' => '+250788123456'])->assertUnauthorized();
        Http::assertNothingSent();
    }

    private function preventTenantBinding(): void {
        $manager = \Mockery::mock(DatabaseProxyManagerService::class);
        $manager->shouldNotReceive('runForCompany');
        $this->app->instance(DatabaseProxyManagerService::class, $manager);
    }

    private function postSigned(array $payload, string $secret = 'secret-1') {
        return $this->postBody(json_encode($payload, JSON_THROW_ON_ERROR), $secret);
    }

    private function postBody(string $body, string $secret = 'secret-1') {
        return $this->call('POST', '/api/whatsapp/webhook/1', server: [
            'HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $body, $secret),
        ], content: $body);
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Plugins\WhatsApp\Services\WhatsAppCredentialService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\WhatsAppTestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class WhatsAppCredentialEndpointTest extends WhatsAppTestCase {
    private function authenticatedHeaders(): array {
        $user = new User();
        $user->id = 1;
        $user->company_id = 1;

        return ['Authorization' => 'Bearer '.JWTAuth::fromUser($user)];
    }

    public function testCredentialEndpointsExposeOnlyMasksAndPresenceIndicators(): void {
        $headers = $this->authenticatedHeaders();
        $this->putJson('/api/whatsapp/credential', [
            'api_access_token' => 'test-access', 'webhook_verify_token' => 'test-verify', 'meta_app_secret' => 'test-secret',
        ], $headers)->assertCreated()->assertJsonPath('data.has_meta_app_secret', true)->assertJsonPath('data.meta_app_secret', '••••••••');
        $response = $this->getJson('/api/whatsapp/credential', $headers)->assertOk()->assertJsonPath('data.has_api_access_token', true);
        foreach (['test-access', 'test-verify', 'test-secret'] as $secret) {
            $this->assertStringNotContainsString($secret, $response->getContent());
        }
        $this->putJson('/api/whatsapp/credential', ['sender_phone_number' => '+250788123456'], $headers)->assertOk();
        $this->assertSame('test-secret', Crypt::decryptString(DB::connection('tenant')->table('whatsapp_credentials')->value('meta_app_secret')));
        $this->putJson('/api/whatsapp/credential', ['meta_app_secret' => null], $headers)->assertOk()->assertJsonPath('data.has_meta_app_secret', false);
    }

    public function testEmptyAndMaskedSecretsCannotBecomeNullClearsThroughMiddleware(): void {
        $headers = $this->authenticatedHeaders();
        resolve(WhatsAppCredentialService::class)->updateCredentials(['meta_app_secret' => 'test-secret']);
        $before = DB::connection('tenant')->table('whatsapp_credentials')->first();
        foreach (['', '  ', '••••••••', '********'] as $invalid) {
            $this->putJson('/api/whatsapp/credential', ['meta_app_secret' => $invalid], $headers)->assertUnprocessable();
            $this->assertEquals($before, DB::connection('tenant')->table('whatsapp_credentials')->first());
        }
    }

    public function testMultipartEmptySecretsCannotClearAnExistingValue(): void {
        resolve(WhatsAppCredentialService::class)->updateCredentials(['meta_app_secret' => 'test-secret']);
        $headers = $this->authenticatedHeaders();
        $this->call('PUT', '/api/whatsapp/credential', ['meta_app_secret' => ''], server: [
            'HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => $headers['Authorization'],
            'CONTENT_TYPE' => 'multipart/form-data; boundary=test',
        ])->assertUnprocessable();
        $this->assertSame('test-secret', Crypt::decryptString(DB::connection('tenant')->table('whatsapp_credentials')->value('meta_app_secret')));
    }

    public function testExistingTestMessageEndpointStillUsesSendClientContract(): void {
        resolve(WhatsAppCredentialService::class)->updateCredentials(['phone_number_id' => 'phone-1', 'api_access_token' => 'test-access']);
        Http::fake(['https://graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.regression']]], 200)]);
        $this->postJson('/api/whatsapp/test-message', ['recipient_phone' => '+250788123456', 'message' => 'Test fixture'], $this->authenticatedHeaders())
            ->assertOk()->assertJsonPath('data.message_id', 'wamid.regression')->assertJsonPath('data.notification_type', 'test');
        Http::assertSentCount(1);
    }
}

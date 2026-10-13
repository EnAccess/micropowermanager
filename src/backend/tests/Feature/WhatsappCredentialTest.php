<?php

namespace Tests\Feature;

use App\Models\User;
use App\Plugins\WhatsappMessaging\Models\WhatsappCredential;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsappCredentialTest extends TestCase {
    private const string ACCESS_TOKEN = 'EAAGtestAccessToken1234567890WXYZ';
    private const string APP_SECRET = 'test-app-secret-abcd';
    private const string PHONE_NUMBER_ID = '106540352242922';
    private const string GRAPH_URL = 'https://graph.facebook.com/v26.0/106540352242922*';

    private User $user;

    protected function setUp(): void {
        parent::setUp();
        $this->user = UserFactory::new()->create();
        $this->user->syncRoles('admin');
    }

    private function installCredentials(): void {
        $this->artisan('whatsapp-messaging:install')->assertSuccessful();
    }

    private function storeConfiguredCredentials(): void {
        WhatsappCredential::query()->create([
            'access_token' => Crypt::encryptString(self::ACCESS_TOKEN),
            'app_secret' => Crypt::encryptString(self::APP_SECRET),
            'webhook_verify_token' => Crypt::encryptString('verify-token'),
            'phone_number_id' => self::PHONE_NUMBER_ID,
            'business_account_id' => '123456789',
            'graph_api_version' => 'v26.0',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array {
        return array_merge([
            'phone_number_id' => self::PHONE_NUMBER_ID,
            'business_account_id' => '123456789',
            'access_token' => self::ACCESS_TOKEN,
            'app_secret' => self::APP_SECRET,
            'graph_api_version' => 'v26.0',
            'sms_fallback_enabled' => true,
        ], $overrides);
    }

    private function rawCredentialRow(): object {
        return DB::connection('tenant')->table('whatsapp_credentials')->first();
    }

    public function testInstallCommandCreatesCredentialRowWithVerifyToken(): void {
        $this->installCredentials();

        $row = $this->rawCredentialRow();
        $this->assertNotNull($row);
        $this->assertNull($row->access_token);
        $this->assertNull($row->app_secret);
        $this->assertSame(40, strlen(Crypt::decryptString($row->webhook_verify_token)));
    }

    public function testInstallCommandIsIdempotent(): void {
        $this->installCredentials();
        $this->installCredentials();

        $this->assertSame(1, WhatsappCredential::query()->count());
    }

    public function testUpdateStoresSecretsEncrypted(): void {
        $this->installCredentials();

        $response = $this->actingAs($this->user)->putJson('/api/whatsapp-messaging/credential', $this->validPayload());

        $response->assertOk();
        $row = $this->rawCredentialRow();
        $this->assertNotSame(self::ACCESS_TOKEN, $row->access_token);
        $this->assertNotSame(self::APP_SECRET, $row->app_secret);
        $this->assertSame(self::ACCESS_TOKEN, Crypt::decryptString($row->access_token));
        $this->assertSame(self::APP_SECRET, Crypt::decryptString($row->app_secret));
        $this->assertSame(self::PHONE_NUMBER_ID, $row->phone_number_id);
        $this->assertEquals(1, $row->sms_fallback_enabled);
    }

    public function testUpdateWithEmptySecretsKeepsStoredValues(): void {
        $this->installCredentials();
        $this->actingAs($this->user)->putJson('/api/whatsapp-messaging/credential', $this->validPayload())->assertOk();

        $response = $this->actingAs($this->user)->putJson(
            '/api/whatsapp-messaging/credential',
            $this->validPayload(['access_token' => '', 'app_secret' => null, 'graph_api_version' => 'v25.0'])
        );

        $response->assertOk();
        $row = $this->rawCredentialRow();
        $this->assertSame(self::ACCESS_TOKEN, Crypt::decryptString($row->access_token));
        $this->assertSame(self::APP_SECRET, Crypt::decryptString($row->app_secret));
        $this->assertSame('v25.0', $row->graph_api_version);
    }

    public function testUpdateStoresAndReturnsCostSettings(): void {
        $this->installCredentials();

        $response = $this->actingAs($this->user)->putJson(
            '/api/whatsapp-messaging/credential',
            $this->validPayload(['sms_unit_cost' => 0.05, 'whatsapp_utility_cost' => '0.0125', 'cost_currency' => 'usd'])
        );

        $response->assertOk();
        $response->assertJsonPath('data.cost_currency', 'USD');
        $this->assertEquals(0.05, $response->json('data.sms_unit_cost'));
        $this->assertEquals(0.0125, $response->json('data.whatsapp_utility_cost'));
    }

    public function testUpdateOnlyChangesCostSettingsThatAreSent(): void {
        $this->installCredentials();
        $this->actingAs($this->user)->putJson(
            '/api/whatsapp-messaging/credential',
            $this->validPayload(['sms_unit_cost' => 0.05, 'whatsapp_utility_cost' => 0.01, 'cost_currency' => 'TZS'])
        )->assertOk();

        $this->actingAs($this->user)->putJson('/api/whatsapp-messaging/credential', $this->validPayload(['sms_unit_cost' => null]))->assertOk();

        $row = $this->rawCredentialRow();
        $this->assertNull($row->sms_unit_cost);
        $this->assertEquals(0.01, $row->whatsapp_utility_cost);
        $this->assertSame('TZS', $row->cost_currency);
    }

    public function testUpdateRejectsInvalidInput(): void {
        $this->installCredentials();

        $response = $this->actingAs($this->user)->putJson(
            '/api/whatsapp-messaging/credential',
            $this->validPayload([
                'phone_number_id' => 'abc',
                'graph_api_version' => '26',
                'sms_fallback_enabled' => 'maybe',
                'sms_unit_cost' => -1,
                'cost_currency' => 'DOLLAR',
            ])
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['phone_number_id', 'graph_api_version', 'sms_fallback_enabled', 'sms_unit_cost', 'cost_currency']);
    }

    public function testShowNeverReturnsFullSecrets(): void {
        $this->installCredentials();
        $this->actingAs($this->user)->putJson('/api/whatsapp-messaging/credential', $this->validPayload())->assertOk();

        $response = $this->actingAs($this->user)->getJson('/api/whatsapp-messaging/credential');

        $response->assertOk();
        $response->assertJsonPath('data.is_access_token_set', true);
        $response->assertJsonPath('data.access_token_last_four', 'WXYZ');
        $response->assertJsonPath('data.is_app_secret_set', true);
        $response->assertJsonPath('data.app_secret_last_four', 'abcd');
        $response->assertJsonPath('data.sms_fallback_enabled', true);
        $response->assertJsonPath(
            'data.webhook_url',
            config('app.url').'/api/whatsapp-messaging/webhook/'.$this->user->company_id
        );
        $this->assertNotEmpty($response->json('data.webhook_verify_token'));
        $this->assertStringNotContainsString(self::ACCESS_TOKEN, $response->getContent());
        $this->assertStringNotContainsString(self::APP_SECRET, $response->getContent());
    }

    public function testCredentialRoutesRequireAuthentication(): void {
        $this->getJson('/api/whatsapp-messaging/credential')->assertUnauthorized();
        $this->putJson('/api/whatsapp-messaging/credential', $this->validPayload())->assertUnauthorized();
        $this->postJson('/api/whatsapp-messaging/credential/test')->assertUnauthorized();
    }

    public function testConnectionReturnsPhoneNumberInfo(): void {
        $this->storeConfiguredCredentials();
        Http::fake([self::GRAPH_URL => Http::response([
            'display_phone_number' => '+1 555-078-3881',
            'verified_name' => 'Test Mini-Grid',
            'quality_rating' => 'GREEN',
            'id' => self::PHONE_NUMBER_ID,
        ])]);

        $response = $this->actingAs($this->user)->postJson('/api/whatsapp-messaging/credential/test');

        $response->assertOk();
        $response->assertExactJson(['data' => [
            'display_phone_number' => '+1 555-078-3881',
            'verified_name' => 'Test Mini-Grid',
            'quality_rating' => 'GREEN',
        ]]);
        Http::assertSent(fn ($request): bool => $request->hasHeader('Authorization', 'Bearer '.self::ACCESS_TOKEN)
            && str_contains($request->url(), 'fields=display_phone_number%2Cverified_name%2Cquality_rating'));
    }

    public function testShowReturnsOptInLinkAfterASuccessfulConnectionTest(): void {
        $this->storeConfiguredCredentials();
        Http::fake([self::GRAPH_URL => Http::response(['display_phone_number' => '+1 555-078-3881'])]);

        $this->actingAs($this->user)->getJson('/api/whatsapp-messaging/credential')->assertJsonPath('data.opt_in_link', null);
        $this->actingAs($this->user)->postJson('/api/whatsapp-messaging/credential/test')->assertOk();

        $this->actingAs($this->user)->getJson('/api/whatsapp-messaging/credential')
            ->assertJsonPath('data.opt_in_link', 'https://wa.me/15550783881?text=JOIN');
    }

    public function testChangingThePhoneNumberIdClearsTheOptInLink(): void {
        $this->storeConfiguredCredentials();
        WhatsappCredential::query()->update(['display_phone_number' => '+1 555-078-3881']);

        $this->actingAs($this->user)
            ->putJson('/api/whatsapp-messaging/credential', $this->validPayload(['phone_number_id' => '999999999999999']))
            ->assertOk()
            ->assertJsonPath('data.opt_in_link', null);
    }

    public function testConnectionWithInvalidTokenReturnsMetaMessage(): void {
        $this->storeConfiguredCredentials();
        Http::fake([self::GRAPH_URL => Http::response([
            'error' => [
                'message' => 'Invalid OAuth access token - Cannot parse access token',
                'type' => 'OAuthException',
                'code' => 190,
            ],
        ], 401)]);

        $response = $this->actingAs($this->user)->postJson('/api/whatsapp-messaging/credential/test');

        $response->assertStatus(502);
        $response->assertJsonPath('message', 'WhatsApp rejected the request: Invalid OAuth access token - Cannot parse access token');
        $this->assertStringNotContainsString(self::ACCESS_TOKEN, $response->getContent());
        $this->assertStringNotContainsString(self::APP_SECRET, $response->getContent());
    }

    public function testConnectionWithoutMetaErrorBodyFallsBackToStatus(): void {
        $this->storeConfiguredCredentials();
        Http::fake([self::GRAPH_URL => Http::response('Bad gateway', 502)]);

        $response = $this->actingAs($this->user)->postJson('/api/whatsapp-messaging/credential/test');

        $response->assertStatus(502);
        $response->assertJsonPath('message', 'WhatsApp rejected the request: HTTP 502');
    }

    public function testConnectionFailureReturnsReadableError(): void {
        $this->storeConfiguredCredentials();
        Http::fake([self::GRAPH_URL => Http::failedConnection()]);

        $response = $this->actingAs($this->user)->postJson('/api/whatsapp-messaging/credential/test');

        $response->assertStatus(502);
        $response->assertJsonPath('message', 'Could not reach the WhatsApp Cloud API. Please try again later.');
    }

    public function testConnectionWithMalformedResponseFails(): void {
        $this->storeConfiguredCredentials();
        Http::fake([self::GRAPH_URL => Http::response(['unexpected' => true])]);

        $response = $this->actingAs($this->user)->postJson('/api/whatsapp-messaging/credential/test');

        $response->assertStatus(502);
        $response->assertJsonPath('message', 'WhatsApp returned an unexpected response for the phone number.');
    }

    public function testConnectionWithIncompleteCredentialsDoesNotCallMeta(): void {
        $this->installCredentials();
        Http::fake();

        $response = $this->actingAs($this->user)->postJson('/api/whatsapp-messaging/credential/test');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['credential']);
        Http::assertNothingSent();
    }
}

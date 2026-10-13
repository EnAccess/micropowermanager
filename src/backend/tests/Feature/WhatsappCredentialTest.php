<?php

namespace Tests\Feature;

use App\Models\User;
use App\Plugins\WhatsappMessaging\Models\WhatsappCredential;
use Database\Factories\UserFactory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsappCredentialTest extends TestCase {
    private const string ACCESS_TOKEN = 'EAAGtestAccessToken1234567890WXYZ';
    private const string APP_SECRET = 'test-app-secret-abcd';
    private const string PHONE_NUMBER_ID = '106540352242922';
    private const string BUSINESS_ACCOUNT_ID = '123456789';
    private const string PHONE_NUMBER_URL = 'https://graph.facebook.com/v26.0/'.self::PHONE_NUMBER_ID.'*';
    private const string TEMPLATES_URL = 'https://graph.facebook.com/v26.0/'.self::BUSINESS_ACCOUNT_ID.'/message_templates*';

    private User $user;

    protected function setUp(): void {
        parent::setUp();
        $this->user = UserFactory::new()->create();
        $this->user->syncRoles('admin');
    }

    public function testInstallCommandCreatesCredentialRowWithVerifyToken(): void {
        Http::fake();

        $this->installCredentials();

        $row = $this->rawCredentialRow();
        $this->assertNull($row->access_token);
        $this->assertNull($row->app_secret);
        $this->assertSame(40, strlen(Crypt::decryptString($row->webhook_verify_token)));
        Http::assertNothingSent();
    }

    public function testInstallCommandIsIdempotent(): void {
        $this->installCredentials();
        $this->installCredentials();

        $this->assertSame(1, WhatsappCredential::query()->count());
    }

    public function testInstallCommandSubmitsTemplatesForAnAlreadyConfiguredCompany(): void {
        $this->installCredentials();
        WhatsappCredential::query()->update([
            'access_token' => Crypt::encryptString(self::ACCESS_TOKEN),
            'app_secret' => Crypt::encryptString(self::APP_SECRET),
            'phone_number_id' => self::PHONE_NUMBER_ID,
            'business_account_id' => self::BUSINESS_ACCOUNT_ID,
        ]);
        $this->fakeMeta();

        $this->installCredentials();

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST' && str_contains($request->url(), '/message_templates'));
    }

    public function testUpdateChecksTheCredentialsWithMetaAndStoresThemEncrypted(): void {
        $this->installCredentials();
        $this->fakeMeta();

        $response = $this->actingAs($this->user)->putJson('/api/whatsapp-messaging/credential', $this->validPayload());

        $response->assertOk();
        $response->assertJsonPath('data.opt_in_link', 'https://wa.me/15550783881?text=JOIN');
        $response->assertJsonPath('template_errors', []);
        $row = $this->rawCredentialRow();
        $this->assertSame(self::ACCESS_TOKEN, Crypt::decryptString($row->access_token));
        $this->assertSame(self::APP_SECRET, Crypt::decryptString($row->app_secret));
        $this->assertSame(self::PHONE_NUMBER_ID, $row->phone_number_id);
        $this->assertSame('+1 555-078-3881', $row->display_phone_number);
        Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer '.self::ACCESS_TOKEN)
            && str_starts_with($request->url(), 'https://graph.facebook.com/v26.0/'.self::PHONE_NUMBER_ID.'?'));
    }

    public function testUpdateSubmitsTheNotificationTemplates(): void {
        $this->installCredentials();
        $this->fakeMeta();

        $this->actingAs($this->user)->putJson('/api/whatsapp-messaging/credential', $this->validPayload())->assertOk();

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && str_contains($request->url(), self::BUSINESS_ACCOUNT_ID.'/message_templates')
            && $request['category'] === 'UTILITY');
    }

    public function testUpdateWithEmptySecretsKeepsStoredValues(): void {
        $this->installCredentials();
        $this->fakeMeta();
        $this->actingAs($this->user)->putJson('/api/whatsapp-messaging/credential', $this->validPayload())->assertOk();

        $response = $this->actingAs($this->user)->putJson(
            '/api/whatsapp-messaging/credential',
            $this->validPayload(['access_token' => '', 'app_secret' => null])
        );

        $response->assertOk();
        $row = $this->rawCredentialRow();
        $this->assertSame(self::ACCESS_TOKEN, Crypt::decryptString($row->access_token));
        $this->assertSame(self::APP_SECRET, Crypt::decryptString($row->app_secret));
    }

    public function testUpdateRejectedByMetaSavesNothingAndShowsMetasReason(): void {
        $this->installCredentials();
        Http::fake([self::PHONE_NUMBER_URL => Http::response([
            'error' => ['message' => 'Invalid OAuth access token - Cannot parse access token', 'type' => 'OAuthException', 'code' => 190],
        ], 401)]);

        $response = $this->actingAs($this->user)->putJson('/api/whatsapp-messaging/credential', $this->validPayload());

        $response->assertStatus(502);
        $this->assertStringContainsString('Invalid OAuth access token', $response->json('message'));
        $this->assertStringNotContainsString(self::ACCESS_TOKEN, $response->getContent());
        $this->assertStringNotContainsString(self::APP_SECRET, $response->getContent());
        $this->assertNull($this->rawCredentialRow()->access_token);
    }

    public function testUpdateReportsAnUnreachableMeta(): void {
        $this->installCredentials();
        Http::fake([self::PHONE_NUMBER_URL => Http::failedConnection()]);

        $response = $this->actingAs($this->user)->putJson('/api/whatsapp-messaging/credential', $this->validPayload());

        $response->assertStatus(502);
        $this->assertStringContainsString('Could not reach the WhatsApp Cloud API', $response->json('message'));
    }

    public function testUpdateRejectsInvalidInput(): void {
        $this->installCredentials();

        $response = $this->actingAs($this->user)->putJson(
            '/api/whatsapp-messaging/credential',
            $this->validPayload(['phone_number_id' => 'abc', 'graph_api_version' => '26'])
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['phone_number_id', 'graph_api_version']);
    }

    public function testShowNeverReturnsFullSecrets(): void {
        $this->installCredentials();
        $this->fakeMeta();
        $this->actingAs($this->user)->putJson('/api/whatsapp-messaging/credential', $this->validPayload())->assertOk();

        $response = $this->actingAs($this->user)->getJson('/api/whatsapp-messaging/credential');

        $response->assertOk();
        $response->assertJsonPath('data.is_access_token_set', true);
        $response->assertJsonPath('data.access_token_last_four', 'WXYZ');
        $response->assertJsonPath('data.is_app_secret_set', true);
        $response->assertJsonPath('data.app_secret_last_four', 'abcd');
        $response->assertJsonPath(
            'data.webhook_url',
            config('app.url').'/api/whatsapp-messaging/webhook/'.$this->user->company_id
        );
        $this->assertNotEmpty($response->json('data.webhook_verify_token'));
        $this->assertStringNotContainsString(self::ACCESS_TOKEN, $response->getContent());
        $this->assertStringNotContainsString(self::APP_SECRET, $response->getContent());
    }

    public function testShowHasNoOptInLinkBeforeTheCredentialsAreSaved(): void {
        $this->installCredentials();

        $this->actingAs($this->user)->getJson('/api/whatsapp-messaging/credential')->assertJsonPath('data.opt_in_link', null);
    }

    public function testCredentialRoutesRequireAuthentication(): void {
        $this->getJson('/api/whatsapp-messaging/credential')->assertUnauthorized();
        $this->putJson('/api/whatsapp-messaging/credential', $this->validPayload())->assertUnauthorized();
    }

    private function installCredentials(): void {
        $this->artisan('whatsapp-messaging:install')->assertSuccessful();
    }

    private function fakeMeta(): void {
        Http::fake([
            self::TEMPLATES_URL => fn (Request $request) => $request->method() === 'GET'
                ? Http::response(['data' => []])
                : Http::response(['id' => '1', 'status' => 'PENDING', 'category' => 'UTILITY']),
            self::PHONE_NUMBER_URL => Http::response(['display_phone_number' => '+1 555-078-3881', 'id' => self::PHONE_NUMBER_ID]),
        ]);
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array {
        return array_merge([
            'phone_number_id' => self::PHONE_NUMBER_ID,
            'business_account_id' => self::BUSINESS_ACCOUNT_ID,
            'access_token' => self::ACCESS_TOKEN,
            'app_secret' => self::APP_SECRET,
            'graph_api_version' => 'v26.0',
        ], $overrides);
    }

    private function rawCredentialRow(): object {
        return DB::connection('tenant')->table('whatsapp_credentials')->first();
    }
}

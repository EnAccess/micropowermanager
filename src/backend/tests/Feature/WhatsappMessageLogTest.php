<?php

namespace Tests\Feature;

use App\Models\MpmPlugin;
use App\Models\Sms;
use App\Models\SmsAttempt;
use App\Models\User;
use App\Plugins\WhatsappMessaging\Models\WhatsappCredential;
use Database\Factories\UserFactory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class WhatsappMessageLogTest extends TestCase {
    use RefreshMultipleDatabases;

    private const string PRICING_URL = '/api/whatsapp-messaging/messages/pricing';
    private const string RECEIVER = '+255700000002';

    private User $user;

    protected function setUp(): void {
        parent::setUp();
        $this->user = UserFactory::new()->create();
    }

    public function testSmsHistoryShowsEveryAttemptOfAMessage(): void {
        $sms = Sms::query()->create([
            'receiver' => self::RECEIVER,
            'body' => 'Your token is 1234',
            'direction' => Sms::DIRECTION_OUTGOING,
            'status' => Sms::STATUS_SENT,
            'gateway_id' => MpmPlugin::TEXTBEE_SMS_GATEWAY,
            'attempts' => 1,
        ]);
        SmsAttempt::query()->create([
            'sms_id' => $sms->id,
            'gateway_id' => MpmPlugin::WHATSAPP_MESSAGING,
            'external_id' => 'wamid.1',
            'message_type' => SmsAttempt::MESSAGE_TYPE_TEMPLATE,
            'template_name' => 'token_confirmation_meter',
            'request' => ['type' => 'template'],
            'status' => SmsAttempt::STATUS_FAILED,
            'error_code' => 131026,
            'error_message' => 'Message undeliverable',
        ]);
        SmsAttempt::query()->create(['sms_id' => $sms->id, 'gateway_id' => MpmPlugin::TEXTBEE_SMS_GATEWAY, 'status' => SmsAttempt::STATUS_ACCEPTED]);

        $response = $this->actingAs($this->user)->getJson('/api/sms/phone/'.self::RECEIVER)->assertOk();

        $response->assertJsonPath('data.0.gateway_id', MpmPlugin::TEXTBEE_SMS_GATEWAY);
        $response->assertJsonPath('data.0.attempts', 1);
        $response->assertJsonPath('data.0.delivery_attempts.0.template_name', 'token_confirmation_meter');
        $response->assertJsonPath('data.0.delivery_attempts.0.error_code', 131026);
        $response->assertJsonPath('data.0.delivery_attempts.1.gateway_id', MpmPlugin::TEXTBEE_SMS_GATEWAY);
        // The request can hold the customer's token, so it never leaves the server.
        $response->assertJsonMissingPath('data.0.delivery_attempts.0.request');
    }

    public function testTheMigrationMovesEveryWhatsappMessageIntoSmsHistory(): void {
        $migration = require database_path('migrations/tenant/2026_11_02_000002_create_sms_attempts_table.php');
        $migration->down();
        foreach (['wamid.1' => 'read', 'wamid.2' => 'failed', 'wamid.3' => 'accepted'] as $wamid => $status) {
            DB::connection('tenant')->table('whatsapp_messages')->insert([
                'sms_id' => 7,
                'whatsapp_contact_id' => 3,
                'wamid' => $wamid,
                'message_type' => 'template',
                'template_name' => 'token_confirmation_meter',
                'status' => $status,
                'error_code' => $status === 'failed' ? 131026 : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        try {
            $migration->up();

            $this->assertFalse(Schema::connection('tenant')->hasTable('whatsapp_messages'));
            $this->assertSame(
                ['wamid.1' => 'read', 'wamid.2' => 'failed', 'wamid.3' => 'accepted'],
                SmsAttempt::query()->where('gateway_id', MpmPlugin::WHATSAPP_MESSAGING)->orderBy('id')->pluck('status', 'external_id')->all(),
            );
            $this->assertSame(131026, SmsAttempt::query()->where('external_id', 'wamid.2')->sole()->error_code);
        } finally {
            // Creating and dropping tables commits MySQL's open transaction, so the test's rollback cannot remove these rows.
            SmsAttempt::query()->where('sms_id', 7)->delete();
        }
    }

    public function testPricingReturnsMetasPricingAnalyticsWithoutStoringIt(): void {
        WhatsappCredential::query()->create([
            'access_token' => Crypt::encryptString('test-access-token'),
            'app_secret' => Crypt::encryptString('test-app-secret'),
            'webhook_verify_token' => Crypt::encryptString('test-verify-token'),
            'phone_number_id' => '1234567890',
            'business_account_id' => '9876543210',
        ]);
        $pricing = ['currency' => 'USD', 'pricing_analytics' => ['data' => [['data_points' => [
            ['start' => 1760000000, 'end' => 1760086400, 'pricing_category' => 'UTILITY', 'pricing_type' => 'REGULAR', 'volume' => 3, 'cost' => 0.024],
            ['start' => 1760000000, 'end' => 1760086400, 'pricing_category' => 'SERVICE', 'pricing_type' => 'FREE_CUSTOMER_SERVICE', 'volume' => 5, 'cost' => 0],
        ]]]], 'id' => '9876543210'];
        Http::fake(['https://graph.facebook.com/v26.0/9876543210*' => Http::response($pricing)]);

        $response = $this->actingAs($this->user)->getJson(self::PRICING_URL.'?from=2025-10-09&to=2025-10-10');

        $response->assertOk()->assertExactJson(['data' => $pricing]);
        Http::assertSent(fn (Request $request): bool => str_contains((string) $request['fields'], 'pricing_analytics.start(1759968000).end(1760140799).granularity(DAILY).dimensions(["PRICING_CATEGORY","PRICING_TYPE"])'));
    }

    public function testTheWhatsappMessageLogEndpointIsGone(): void {
        $this->actingAs($this->user)->getJson('/api/whatsapp-messaging/messages')->assertNotFound();
    }

    public function testPricingRequiresLogin(): void {
        $this->getJson(self::PRICING_URL)->assertUnauthorized();
    }
}

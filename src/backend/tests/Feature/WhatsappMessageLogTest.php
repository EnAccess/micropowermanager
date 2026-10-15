<?php

namespace Tests\Feature;

use App\Models\MpmPlugin;
use App\Models\Sms;
use App\Models\User;
use App\Plugins\WhatsappMessaging\Models\WhatsappCredential;
use App\Plugins\WhatsappMessaging\Models\WhatsappMessage;
use Database\Factories\UserFactory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class WhatsappMessageLogTest extends TestCase {
    use RefreshMultipleDatabases;

    private const string URL = '/api/whatsapp-messaging/messages';

    private User $user;

    protected function setUp(): void {
        parent::setUp();
        $this->user = UserFactory::new()->create();

        $this->createAttempt('+255700000001', WhatsappMessage::STATUS_READ, MpmPlugin::WHATSAPP_MESSAGING, Sms::STATUS_DELIVERED);
        $this->createAttempt('+255700000002', WhatsappMessage::STATUS_FAILED, MpmPlugin::TEXTBEE_SMS_GATEWAY, Sms::STATUS_SENT, 131026);
        $this->createAttempt('+255700000003', WhatsappMessage::STATUS_FAILED, MpmPlugin::WHATSAPP_MESSAGING, Sms::STATUS_FAILED, 131030);
    }

    public function testTheLogShowsTheSmsStatusOfEachMessage(): void {
        $response = $this->actingAs($this->user)->getJson(self::URL)->assertOk();

        $this->assertSame(
            ['+255700000003' => Sms::STATUS_FAILED, '+255700000002' => Sms::STATUS_SENT, '+255700000001' => Sms::STATUS_DELIVERED],
            array_column($response->json('data'), 'sms_status', 'receiver'),
        );
    }

    public function testTheStatusFilterListsTheDeliveryFailures(): void {
        $response = $this->actingAs($this->user)->getJson(self::URL.'?status='.WhatsappMessage::STATUS_FAILED)->assertOk();

        $this->assertSame(['+255700000003', '+255700000002'], array_column($response->json('data'), 'receiver'));
        $response->assertJsonPath('data.0.error_code', 131030);
        $response->assertJsonPath('data.0.error_message', 'Error 131030');
        $response->assertJsonPath('data.0.sms_status', Sms::STATUS_FAILED);
    }

    public function testThePhoneFilterMatchesDigits(): void {
        $response = $this->actingAs($this->user)->getJson(self::URL.'?phone='.urlencode('+255 700 000 002'));

        $this->assertSame(['+255700000002'], array_column($response->json('data'), 'receiver'));
    }

    public function testPerPageAboveTheLimitIsRejected(): void {
        $this->actingAs($this->user)->getJson(self::URL.'?per_page=500')->assertStatus(422)->assertJsonValidationErrors('per_page');
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
            ['start' => 1760000000, 'end' => 1760086400, 'pricing_type' => 'REGULAR', 'volume' => 3, 'cost' => 0.024],
            ['start' => 1760000000, 'end' => 1760086400, 'pricing_type' => 'FREE_CUSTOMER_SERVICE', 'volume' => 5, 'cost' => 0],
        ]]]], 'id' => '9876543210'];
        Http::fake(['https://graph.facebook.com/v26.0/9876543210*' => Http::response($pricing)]);

        $response = $this->actingAs($this->user)->getJson(self::URL.'/pricing?from=2025-10-09&to=2025-10-10');

        $response->assertOk()->assertExactJson(['data' => $pricing]);
        Http::assertSent(fn (Request $request): bool => str_contains((string) $request['fields'], 'pricing_analytics.start(1759968000).end(1760140799)'));
    }

    public function testTheLogRequiresLogin(): void {
        $this->getJson(self::URL)->assertUnauthorized();
        $this->getJson(self::URL.'/pricing')->assertUnauthorized();
    }

    private function createAttempt(string $receiver, string $whatsappStatus, int $gatewayId, int $smsStatus, ?int $errorCode = null): void {
        $sms = Sms::query()->create([
            'receiver' => $receiver,
            'body' => 'Body for '.$receiver,
            'direction' => Sms::DIRECTION_OUTGOING,
            'status' => $smsStatus,
            'gateway_id' => $gatewayId,
            'attempts' => 1,
        ]);

        WhatsappMessage::query()->create([
            'sms_id' => $sms->id,
            'wamid' => 'wamid.'.$receiver,
            'message_type' => WhatsappMessage::MESSAGE_TYPE_TEMPLATE,
            'template_name' => 'mpm_token_confirmation_meter',
            'status' => $whatsappStatus,
            'error_code' => $errorCode,
            'error_message' => $errorCode === null ? null : 'Error '.$errorCode,
            'failed_at' => $errorCode === null ? null : now(),
        ]);
    }
}

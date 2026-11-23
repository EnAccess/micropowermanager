<?php

namespace Tests\Feature;

use App\Models\MpmPlugin;
use App\Models\Plugins;
use App\Models\User;
use App\Plugins\WhatsappMessaging\Models\WhatsappCredential;
use App\Plugins\WhatsappMessaging\Models\WhatsappTemplate;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class WhatsappTemplatesPageTest extends TestCase {
    use RefreshMultipleDatabases;

    private const string URL = '/api/whatsapp-messaging/templates';

    private User $user;

    protected function setUp(): void {
        parent::setUp();
        $this->user = UserFactory::new()->create();
    }

    public function testTheListShowsEachTemplateWithItsUseAndWhyItWasRejected(): void {
        $this->template('token_confirmation_meter', 'APPROVED', 'Hello {{name}}, your token is {{token}}.');
        $this->template('outage_notice', 'REJECTED', 'Power in {{1}} is off.', ['rejected_reason' => 'INVALID_FORMAT', 'rejection_detail' => 'A variable ends the body.', 'rejection_recommendation' => 'Add text after {{1}}.']);

        $response = $this->actingAs($this->user)->getJson(self::URL)->assertOk();

        $response->assertJsonPath('data.0.name', 'outage_notice');
        $response->assertJsonPath('data.0.used_for', null);
        $response->assertJsonPath('data.0.rejection_detail', 'A variable ends the body.');
        $response->assertJsonPath('data.0.rejection_recommendation', 'Add text after {{1}}.');
        $response->assertJsonPath('data.1.used_for', 'TokenConfirmationMeter');
        $response->assertJsonPath('data.1.parameters', ['name', 'token']);
    }

    public function testTheListIsPaginated(): void {
        foreach (range(1, 3) as $number) {
            $this->template('notice_'.$number, 'APPROVED', 'Notice {{area}} number '.$number.'.');
        }

        $response = $this->actingAs($this->user)->getJson(self::URL.'?per_page=2&page=2')->assertOk();

        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'notice_3');
        $response->assertJsonPath('meta.total', 3);
    }

    public function testEachNotificationSaysWhetherItsTemplateCanBeUsed(): void {
        $this->template('token_confirmation_meter', 'APPROVED', 'Your token is {{token}}.');
        $this->template('appliance_rate_reminder', 'PENDING', 'Pay {{remaining}} by {{due_date}}.');

        $notifications = collect($this->actingAs($this->user)->getJson(self::URL.'/notifications')->assertOk()->json('data'))->keyBy('reference');

        $this->assertNull($notifications['TokenConfirmationMeter']['problem']);
        $this->assertSame('The WhatsApp template "appliance_rate_reminder" is not approved (status: PENDING).', $notifications['ApplianceRateReminder']['problem']);
        $this->assertSame('There is no WhatsApp template named "token_confirmation_shs". Create it in WhatsApp Manager.', $notifications['TokenConfirmationSHS']['problem']);
        $this->assertContains('due_date', $notifications['ApplianceRateReminder']['parameters']);
    }

    public function testThePickerOffersOnlyApprovedTemplatesThatOperatorsMaySend(): void {
        $this->enableWhatsapp();
        $this->template('hello_world', 'APPROVED', 'Welcome and congratulations!');
        $this->template('power_outage_notice', 'APPROVED', 'Dear {{name}}, power in {{area}} is off.');
        $this->template('power_restored_notice', 'PENDING', 'Dear {{name}}, power in {{area}} is back.');
        $this->template('token_confirmation_meter', 'APPROVED', 'Your token is {{token}}.');
        $this->template('outage_with_image', 'APPROVED', 'Power in {{area}} is off.', ['components' => [['type' => 'HEADER', 'format' => 'IMAGE'], ['type' => 'BODY', 'text' => 'Power in {{area}} is off.']]]);

        $response = $this->actingAs($this->user)->getJson(self::URL.'/manual')->assertOk();

        $this->assertSame(['power_outage_notice'], array_column($response->json('data'), 'name'));
    }

    public function testThePickerIsEmptyWhileWhatsappIsOff(): void {
        $this->template('power_outage_notice', 'APPROVED', 'Dear {{name}}, power in {{area}} is off.');

        $this->actingAs($this->user)->getJson(self::URL.'/manual')->assertOk()->assertJsonCount(0, 'data');
    }

    public function testMetasSampleTemplateIsNeitherStoredNorListed(): void {
        $this->template('hello_world', 'APPROVED', 'Welcome and congratulations!');
        $this->enableWhatsapp();
        Http::fake(['https://graph.facebook.com/v26.0/9876543210/message_templates*' => Http::response(['data' => [
            ['id' => '1', 'name' => 'hello_world', 'language' => 'en_US', 'category' => 'UTILITY', 'status' => 'APPROVED', 'components' => [['type' => 'BODY', 'text' => 'Welcome!']]],
            ['id' => '2', 'name' => 'power_outage_notice', 'language' => 'en', 'category' => 'UTILITY', 'status' => 'APPROVED', 'parameter_format' => 'NAMED', 'components' => [['type' => 'BODY', 'text' => 'Power in {{area}} is off.']]],
        ]])]);

        // A row stored before MPM skipped samples is already hidden, and the next refresh removes it.
        $this->actingAs($this->user)->getJson(self::URL)->assertJsonCount(0, 'data');
        $this->actingAs($this->user)->postJson(self::URL.'/refresh')->assertNoContent();

        $this->assertSame(['power_outage_notice'], WhatsappTemplate::query()->pluck('name')->all());
    }

    public function testRefreshReloadsTheTemplatesFromMeta(): void {
        WhatsappCredential::query()->create([
            'access_token' => Crypt::encryptString('test-access-token'),
            'app_secret' => Crypt::encryptString('test-app-secret'),
            'webhook_verify_token' => Crypt::encryptString('test-verify-token'),
            'phone_number_id' => '1234567890',
            'business_account_id' => '9876543210',
        ]);
        Http::fake(['https://graph.facebook.com/v26.0/9876543210/message_templates*' => Http::response(['data' => [[
            'id' => '111',
            'name' => 'outage_notice',
            'language' => 'en',
            'category' => 'UTILITY',
            'status' => 'APPROVED',
            'parameter_format' => 'POSITIONAL',
            'components' => [['type' => 'BODY', 'text' => 'Power in {{1}} is off until {{2}}.']],
        ]]])]);

        $this->actingAs($this->user)->postJson(self::URL.'/refresh')->assertNoContent();

        $this->actingAs($this->user)->getJson(self::URL)->assertJsonPath('data.0.parameters', ['1', '2']);
    }

    public function testTheTemplatesRequireLogin(): void {
        $this->getJson(self::URL)->assertUnauthorized();
        $this->getJson(self::URL.'/notifications')->assertUnauthorized();
        $this->getJson(self::URL.'/manual')->assertUnauthorized();
        $this->postJson(self::URL.'/refresh')->assertUnauthorized();
    }

    private function enableWhatsapp(): void {
        Plugins::query()->create(['mpm_plugin_id' => MpmPlugin::WHATSAPP_MESSAGING, 'status' => Plugins::ACTIVE]);
        WhatsappCredential::query()->create([
            'access_token' => Crypt::encryptString('test-access-token'),
            'app_secret' => Crypt::encryptString('test-app-secret'),
            'webhook_verify_token' => Crypt::encryptString('test-verify-token'),
            'phone_number_id' => '1234567890',
            'business_account_id' => '9876543210',
        ]);
    }

    /** @param array<string, mixed> $attributes */
    private function template(string $name, string $status, string $body, array $attributes = []): void {
        WhatsappTemplate::query()->create([
            'meta_template_id' => $name,
            'name' => $name,
            'language' => 'en',
            'category' => 'UTILITY',
            'status' => $status,
            'parameter_format' => str_contains($body, '{{1}}') ? WhatsappTemplate::PARAMETER_FORMAT_POSITIONAL : WhatsappTemplate::PARAMETER_FORMAT_NAMED,
            'components' => [['type' => 'BODY', 'text' => $body]],
            ...$attributes,
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Plugins\WhatsappMessaging\Models\WhatsappCredential;
use App\Plugins\WhatsappMessaging\Models\WhatsappTemplate;
use App\Plugins\WhatsappMessaging\Services\WhatsappTemplateService;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class WhatsappTemplateTest extends TestCase {
    use RefreshMultipleDatabases;

    private const string TEMPLATES_URL = 'https://graph.facebook.com/v26.0/9876543210/message_templates*';

    private User $user;

    protected function setUp(): void {
        parent::setUp();

        $this->user = UserFactory::new()->create();
        WhatsappCredential::query()->create([
            'access_token' => Crypt::encryptString('test-access-token'),
            'app_secret' => Crypt::encryptString('test-app-secret'),
            'webhook_verify_token' => Crypt::encryptString('test-verify-token'),
            'business_account_id' => '9876543210',
        ]);
        resolve(WhatsappTemplateService::class)->createDefaultMappings();
    }

    public function testInstallSeedsTheFiveDefaultMappings(): void {
        $this->assertSame(
            [
                'ApplianceRateReminder' => 'mpm_appliance_payment_reminder',
                'OverDueApplianceRateReminder' => 'mpm_appliance_payment_overdue',
                'TokenConfirmationMeter' => 'mpm_payment_receipt_meter',
                'TokenConfirmationSHS' => 'mpm_payment_receipt_shs',
                'TransactionConfirmationNoToken' => 'mpm_payment_receipt',
            ],
            WhatsappTemplate::query()->where('is_active', true)->orderBy('body_parser')->pluck('template_name', 'body_parser')->all(),
        );
    }

    public function testListingShowsTheMetaStatusAndFlagsAWrongParameterCount(): void {
        Http::fake([self::TEMPLATES_URL => Http::response(['data' => [
            $this->metaTemplate('mpm_payment_receipt_meter', 'APPROVED', 'Hello {{1}} {{2}}, meter {{3}}. Token {{4}} for {{5}} kWh.'),
            $this->metaTemplate('mpm_appliance_payment_reminder', 'APPROVED', 'Reminder: {{1}} for {{2}}.'),
            $this->metaTemplate('mpm_payment_receipt', 'PENDING', 'Hello {{1}} {{2}}, we received {{3}}.'),
        ]])]);

        $response = $this->actingAs($this->user)->getJson('/api/whatsapp-messaging/templates');

        $response->assertOk();
        $templates = $response->collect('data')->keyBy('body_parser');

        $this->assertSame('APPROVED', $templates['TokenConfirmationMeter']['meta_status']);
        $this->assertSame(5, $templates['TokenConfirmationMeter']['meta_placeholder_count']);
        $this->assertSame([], $templates['TokenConfirmationMeter']['problems']);

        $this->assertSame('APPROVED', $templates['ApplianceRateReminder']['meta_status']);
        $this->assertSame(['Template "mpm_appliance_payment_reminder" has 2 placeholders but 3 parameters are mapped.'], $templates['ApplianceRateReminder']['problems']);

        $this->assertSame('PENDING', $templates['TransactionConfirmationNoToken']['meta_status']);
        $this->assertStringContainsString('is PENDING', $templates['TransactionConfirmationNoToken']['problems'][0]);

        $this->assertNull($templates['TokenConfirmationSHS']['meta_status']);
        $this->assertStringContainsString('does not exist', $templates['TokenConfirmationSHS']['problems'][0]);
    }

    public function testUpdateSavesAMappingThatMatchesTheApprovedTemplate(): void {
        Http::fake([self::TEMPLATES_URL => Http::response(['data' => [
            $this->metaTemplate('mpm_receipt_v2', 'APPROVED', 'Hi {{1}}, we received {{2}}.'),
        ]])]);
        $template = $this->mapping('TransactionConfirmationNoToken');

        $response = $this->actingAs($this->user)->putJson('/api/whatsapp-messaging/templates/'.$template->id, [
            'template_name' => 'mpm_receipt_v2',
            'language_code' => 'en',
            'parameters' => ['name', 'amount'],
            'is_active' => true,
        ]);

        $response->assertOk()->assertJsonPath('data.problems', [])->assertJsonPath('data.meta_status', 'APPROVED');
        $template->refresh();
        $this->assertSame('mpm_receipt_v2', $template->template_name);
        $this->assertSame(['name', 'amount'], $template->parameters);
    }

    public function testUpdateRejectsAParameterCountThatDoesNotMatchTheTemplate(): void {
        Http::fake([self::TEMPLATES_URL => Http::response(['data' => [
            $this->metaTemplate('mpm_payment_receipt', 'APPROVED', 'Hello {{1}} {{2}}, we received {{3}}.'),
        ]])]);
        $template = $this->mapping('TransactionConfirmationNoToken');

        $response = $this->actingAs($this->user)->putJson('/api/whatsapp-messaging/templates/'.$template->id, [
            'template_name' => 'mpm_payment_receipt',
            'language_code' => 'en',
            'parameters' => ['name', 'amount'],
            'is_active' => true,
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['template_name']);
        $this->assertSame(['name', 'surname', 'amount'], $template->refresh()->parameters);
    }

    public function testUpdateCanDeactivateAMappingThatMetaRejects(): void {
        Http::fake([self::TEMPLATES_URL => Http::response(['data' => []])]);
        $template = $this->mapping('TokenConfirmationSHS');

        $response = $this->actingAs($this->user)->putJson('/api/whatsapp-messaging/templates/'.$template->id, [
            'template_name' => 'mpm_payment_receipt_shs',
            'language_code' => 'en',
            'parameters' => ['name', 'surname', 'amount', 'device_serial', 'token', 'duration', 'unit'],
            'is_active' => false,
        ]);

        $response->assertOk();
        $this->assertFalse($template->refresh()->is_active);
    }

    public function testTemplatesRequireAnAuthenticatedUser(): void {
        $this->getJson('/api/whatsapp-messaging/templates')->assertUnauthorized();
    }

    private function mapping(string $bodyParser): WhatsappTemplate {
        return WhatsappTemplate::query()->where('body_parser', $bodyParser)->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function metaTemplate(string $name, string $status, string $body): array {
        return [
            'name' => $name,
            'language' => 'en',
            'status' => $status,
            'category' => 'UTILITY',
            'components' => [['type' => 'BODY', 'text' => $body]],
            'id' => (string) random_int(1, PHP_INT_MAX),
        ];
    }
}

<?php

namespace Tests\Feature;

use App\Models\MainSettings;
use App\Plugins\WhatsappMessaging\Exceptions\WhatsappTemplateNotConfiguredException;
use App\Plugins\WhatsappMessaging\Models\WhatsappCredential;
use App\Plugins\WhatsappMessaging\Models\WhatsappTemplate;
use App\Plugins\WhatsappMessaging\Services\WhatsappTemplateService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class WhatsappTemplateServiceTest extends TestCase {
    use RefreshMultipleDatabases;

    private const string TEMPLATES_URL = 'https://graph.facebook.com/v26.0/9876543210/message_templates';

    private WhatsappCredential $credential;

    protected function setUp(): void {
        parent::setUp();

        $this->credential = new WhatsappCredential(['access_token' => 'test-access-token', 'phone_number_id' => '1234567890', 'business_account_id' => '9876543210', 'graph_api_version' => 'v26.0']);
    }

    public function testRefreshReadsEveryPageAndDropsTemplatesDeletedInWhatsappManager(): void {
        $this->template('deleted_in_whatsapp_manager', metaTemplateId: '111');
        Http::fake([self::TEMPLATES_URL.'*' => fn (Request $request) => ($request['after'] ?? null) === null
            ? Http::response(['data' => [$this->metaTemplate('222', 'token_confirmation_meter')], 'paging' => ['cursors' => ['after' => 'page-2'], 'next' => self::TEMPLATES_URL.'?after=page-2']])
            : Http::response(['data' => [$this->metaTemplate('333', 'outage_notice', 'REJECTED', 'PROMOTIONAL')], 'paging' => ['cursors' => ['after' => 'end']]])]);

        $this->service()->refresh($this->credential);

        $this->assertEqualsCanonicalizing(['222', '333'], WhatsappTemplate::query()->pluck('meta_template_id')->all());
        $this->assertNull(WhatsappTemplate::query()->where('meta_template_id', '222')->sole()->rejected_reason);
        $this->assertSame('PROMOTIONAL', WhatsappTemplate::query()->where('meta_template_id', '333')->sole()->rejected_reason);
        Http::assertSentCount(2);
    }

    public function testRefreshForgetsWebhookExplanationsOnceTheStatusChanges(): void {
        $this->template('token_confirmation_meter', status: 'REJECTED', metaTemplateId: '222')->update(['rejection_detail' => 'Variable at the end', 'rejection_recommendation' => 'Add text after it']);
        Http::fake([self::TEMPLATES_URL.'*' => Http::response(['data' => [$this->metaTemplate('222', 'token_confirmation_meter')]])]);

        $this->service()->refresh($this->credential);

        $template = WhatsappTemplate::query()->sole();
        $this->assertSame(WhatsappTemplate::STATUS_APPROVED, $template->status);
        $this->assertNull($template->rejection_detail);
        $this->assertNull($template->rejection_recommendation);
    }

    public function testANotificationUsesItsApprovedTemplateInTheCompanyLanguage(): void {
        MainSettings::query()->update(['language' => 'fr']);
        $this->template('token_confirmation_meter', language: 'en_US', metaTemplateId: '1');
        $this->template('token_confirmation_meter', language: 'fr', metaTemplateId: '2');

        $this->assertSame('fr', $this->service()->notificationTemplate('TokenConfirmationMeter')->language);
    }

    public function testAReminderMayGreetTheCustomerWithTheHeaderValues(): void {
        $this->template('appliance_rate_reminder', body: 'Dear {{name}} {{surname}}, {{remaining}} for your {{appliance_type_name}} is due on {{due_date}}.');

        $this->assertSame('appliance_rate_reminder', $this->service()->notificationTemplate('ApplianceRateReminder')->name);
        $this->assertSame(['name', 'surname', 'appliance_type_name', 'remaining', 'due_date'], $this->service()->notificationParameters('ApplianceRateReminder'));
    }

    /** @return iterable<string, array{0: string, 1: string}> */
    public static function unusableTemplates(): iterable {
        yield 'missing' => ['', 'There is no WhatsApp template named "token_confirmation_meter". Create it in WhatsApp Manager.'];
        yield 'positional parameters' => ['positional', 'The WhatsApp template "token_confirmation_meter" must use named parameters such as {{name}}, not {{1}}.'];
        yield 'media header' => ['image header', 'The WhatsApp template "token_confirmation_meter" has a media header or a button with a variable, which MPM cannot fill.'];
        yield 'unknown parameter' => ['unknown parameter', 'The WhatsApp template "token_confirmation_meter" uses parameters MPM does not have: balance.'];
    }

    #[DataProvider('unusableTemplates')]
    public function testANotificationWithoutAUsableTemplateSaysWhy(string $problem, string $reason): void {
        match ($problem) {
            'positional' => $this->template('token_confirmation_meter', body: 'Your token is {{1}}.', parameterFormat: WhatsappTemplate::PARAMETER_FORMAT_POSITIONAL),
            'image header' => $this->template('token_confirmation_meter', header: ['type' => 'HEADER', 'format' => 'IMAGE']),
            'unknown parameter' => $this->template('token_confirmation_meter', body: 'Your token is {{token}}, balance {{balance}}.'),
            default => null,
        };

        $this->expectException(WhatsappTemplateNotConfiguredException::class);
        $this->expectExceptionMessage($reason);

        $this->service()->notificationTemplate('TokenConfirmationMeter');
    }

    private function service(): WhatsappTemplateService {
        return resolve(WhatsappTemplateService::class);
    }

    /** @param array<string, mixed>|null $header */
    private function template(
        string $name,
        string $body = 'Your token is {{token}}.',
        string $status = WhatsappTemplate::STATUS_APPROVED,
        string $language = 'en',
        string $parameterFormat = WhatsappTemplate::PARAMETER_FORMAT_NAMED,
        ?array $header = null,
        string $metaTemplateId = '594425479261596',
    ): WhatsappTemplate {
        return WhatsappTemplate::query()->create([
            'meta_template_id' => $metaTemplateId,
            'name' => $name,
            'language' => $language,
            'category' => 'UTILITY',
            'status' => $status,
            'parameter_format' => $parameterFormat,
            'components' => array_values(array_filter([$header, ['type' => 'BODY', 'text' => $body]])),
        ]);
    }

    /** @return array<string, mixed> */
    private function metaTemplate(string $id, string $name, string $status = WhatsappTemplate::STATUS_APPROVED, string $rejectedReason = 'NONE'): array {
        return [
            'id' => $id,
            'name' => $name,
            'language' => 'en',
            'category' => 'UTILITY',
            'status' => $status,
            'parameter_format' => 'NAMED',
            'components' => [['type' => 'BODY', 'text' => 'Your token is {{token}}.']],
            'rejected_reason' => $rejectedReason,
        ];
    }
}

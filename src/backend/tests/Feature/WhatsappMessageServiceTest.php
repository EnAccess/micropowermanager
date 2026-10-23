<?php

namespace Tests\Feature;

use App\Models\MpmPlugin;
use App\Models\Person\Person;
use App\Models\Plugins;
use App\Models\Sms;
use App\Models\SmsBody;
use App\Plugins\WhatsappMessaging\Exceptions\WhatsappApiException;
use App\Plugins\WhatsappMessaging\Exceptions\WhatsappTemplateNotConfiguredException;
use App\Plugins\WhatsappMessaging\Models\WhatsappContact;
use App\Plugins\WhatsappMessaging\Models\WhatsappCredential;
use App\Plugins\WhatsappMessaging\Models\WhatsappMessage;
use App\Plugins\WhatsappMessaging\Services\WhatsappMessageService;
use App\Plugins\WhatsappMessaging\Services\WhatsappTemplateService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class WhatsappMessageServiceTest extends TestCase {
    use RefreshMultipleDatabases;

    private const string MESSAGES_URL = 'https://graph.facebook.com/v26.0/1234567890/messages';
    private const string TEMPLATES_URL = 'https://graph.facebook.com/v26.0/9876543210/message_templates';
    private const string WAMID = 'wamid.HBgMMjU1NzEyMzQ1Njc4FQIAERgSQ0E';
    private const array METER_RECEIPT_VALUES = ['name' => 'Amina', 'surname' => 'Juma', 'meter_serial' => '47000268748', 'token' => '1234-5678', 'energy' => '5.000'];

    private WhatsappCredential $credential;

    protected function setUp(): void {
        parent::setUp();

        $this->credential = new WhatsappCredential(['access_token' => 'test-access-token', 'phone_number_id' => '1234567890', 'business_account_id' => '9876543210', 'graph_api_version' => 'v26.0']);
        SmsBody::query()->where('reference', 'TokenConfirmationMeter')->update(['body' => 'Dear [name] [surname], meter [meter_serial] got token [token] for [energy] kWh']);
    }

    public function testItSendsTheSmsTextAsFreeTextInsideTheWindow(): void {
        Http::fake([self::MESSAGES_URL => Http::response(['messages' => [['id' => self::WAMID]]])]);
        $sms = $this->sms();

        $this->send($sms, $contact = $this->contact(Carbon::now()->subHours(23)), 'TokenConfirmationMeter');

        Http::assertSent(fn (Request $request): bool => $request['type'] === 'text' && $request['text']['body'] === 'Your token is 1234');
        $this->assertDatabaseHas('whatsapp_messages', [
            'sms_id' => $sms->id,
            'whatsapp_contact_id' => $contact->id,
            'wamid' => self::WAMID,
            'message_type' => WhatsappMessage::MESSAGE_TYPE_TEXT,
            'template_name' => null,
            'status' => WhatsappMessage::STATUS_ACCEPTED,
        ], 'tenant');
    }

    public function testItSendsTheTemplateOfTheSmsTextOutsideTheWindow(): void {
        Http::fake([self::MESSAGES_URL => Http::response(['messages' => [['id' => self::WAMID]]])]);
        $sms = $this->sms();

        $this->send($sms, $this->contact(Carbon::now()->subMinutes(23 * 60 + 55)), 'TokenConfirmationMeter');

        Http::assertSent(fn (Request $request): bool => $request['type'] === 'template'
            && $request['template']['name'] === $this->templateName('TokenConfirmationMeter')
            && array_column($request['template']['components'][0]['parameters'], 'text', 'parameter_name') === ['name_surname' => 'Amina Juma', 'meter_serial' => '47000268748', 'token' => '1234-5678', 'energy' => '5.000']);
        $this->assertDatabaseHas('whatsapp_messages', [
            'sms_id' => $sms->id,
            'message_type' => WhatsappMessage::MESSAGE_TYPE_TEMPLATE,
            'template_name' => $this->templateName('TokenConfirmationMeter'),
            'status' => WhatsappMessage::STATUS_ACCEPTED,
        ], 'tenant');
    }

    public function testItLogsAndRethrowsAMessageThatHasNoTemplate(): void {
        Http::fake();
        $sms = $this->sms();

        try {
            $this->send($sms, $this->contact(null), null);
            $this->fail('A manual message is not an MPM notification text, so it has no template.');
        } catch (WhatsappTemplateNotConfiguredException) {
            Http::assertNothingSent();
        }

        $message = WhatsappMessage::query()->where('sms_id', $sms->id)->sole();
        $this->assertSame(WhatsappMessage::STATUS_FAILED, $message->status);
        $this->assertSame(WhatsappMessage::MESSAGE_TYPE_TEMPLATE, $message->message_type);
        $this->assertNotNull($message->failed_at);
    }

    public function testItLogsTheMetaErrorOfAFailedSendAndRethrows(): void {
        Http::fake([self::MESSAGES_URL => Http::response(['error' => ['message' => 'Recipient not allowed', 'code' => 131030]], 400)]);
        $sms = $this->sms();

        try {
            $this->send($sms, $this->contact(Carbon::now()->subHour()), null);
            $this->fail('The Meta error should be rethrown so the gateway falls back to SMS.');
        } catch (WhatsappApiException $exception) {
            $this->assertSame(131030, $exception->getCode());
        }

        $message = WhatsappMessage::query()->where('sms_id', $sms->id)->sole();
        $this->assertSame(WhatsappMessage::STATUS_FAILED, $message->status);
        $this->assertSame(WhatsappMessage::MESSAGE_TYPE_TEXT, $message->message_type);
        $this->assertSame(131030, $message->error_code);
        $this->assertNull($message->wamid);
    }

    public function testSyncSubmitsOnlyTheNotificationTemplatesMetaDoesNotHaveYet(): void {
        $existingName = $this->templateName('TokenConfirmationMeter');
        Http::fake([self::TEMPLATES_URL.'*' => fn (Request $request) => match (true) {
            $request->method() === 'POST' => Http::response(['id' => '1', 'status' => 'PENDING']),
            // Meta's name filter matches partially, so a longer name must not count as the template existing.
            $request['name'] === $existingName => Http::response(['data' => [['name' => $existingName]]]),
            default => Http::response(['data' => [['name' => $request['name'].'_old']]]),
        }]);

        $errors = resolve(WhatsappTemplateService::class)->sync($this->credential);

        $this->assertSame([], $errors);
        // The overdue sender spells its reference "OverDue…" while the row says "Overdue…"; sync must submit the name that sending uses.
        $this->assertEqualsCanonicalizing(
            array_map($this->templateName(...), ['TokenConfirmationSHS', 'TransactionConfirmationNoToken', 'ApplianceRateReminder', 'OverDueApplianceRateReminder']),
            Http::recorded(fn (Request $request): bool => $request->method() === 'POST')->map(fn (array $exchange): string => $exchange[0]['name'])->all(),
        );
    }

    public function testEditingAnSmsTextSubmitsItsNewTemplateWhileWhatsappIsAvailable(): void {
        Plugins::query()->create(['mpm_plugin_id' => MpmPlugin::WHATSAPP_MESSAGING, 'status' => Plugins::ACTIVE]);
        WhatsappCredential::query()->create([
            'access_token' => Crypt::encryptString('test-access-token'),
            'app_secret' => Crypt::encryptString('test-app-secret'),
            'webhook_verify_token' => Crypt::encryptString('test-verify-token'),
            'phone_number_id' => '1234567890',
            'business_account_id' => '9876543210',
            'graph_api_version' => 'v26.0',
        ]);
        Http::fake([self::TEMPLATES_URL.'*' => fn (Request $request) => $request->method() === 'GET' ? Http::response(['data' => []]) : Http::response(['id' => '1'])]);

        SmsBody::query()->where('reference', 'TokenConfirmationMeter')->firstOrFail()->update(['body' => 'Hello [name], your token is [token] for [energy] kWh']);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST' && $request['name'] === $this->templateName('TokenConfirmationMeter'));
    }

    public function testEditingAnSmsTextSubmitsNothingWhileWhatsappIsOff(): void {
        Http::fake();

        SmsBody::query()->where('reference', 'TokenConfirmationMeter')->firstOrFail()->update(['body' => 'Hello [name], your token is [token] for [energy] kWh']);

        Http::assertNothingSent();
    }

    public function testSyncReportsEachRefusalWithoutStoppingTheOthers(): void {
        Http::fake([self::TEMPLATES_URL.'*' => fn (Request $request) => $request->method() === 'GET'
            ? Http::response(['data' => []])
            : Http::response(['error' => ['code' => 100, 'message' => 'Invalid parameter', 'error_user_msg' => 'Too many variables for the length']], 400)]);

        $errors = resolve(WhatsappTemplateService::class)->sync($this->credential);

        $this->assertCount(5, $errors);
        $this->assertStringContainsString('Too many variables for the length', $errors[0]);
    }

    private function templateName(string $reference): string {
        return resolve(WhatsappTemplateService::class)->message($reference, [])['template']['name'];
    }

    private function send(Sms $sms, WhatsappContact $contact, ?string $reference): void {
        resolve(WhatsappMessageService::class)->send($this->credential, $sms, $contact, 'Your token is 1234', $reference, self::METER_RECEIPT_VALUES);
    }

    private function contact(?Carbon $lastInboundAt): WhatsappContact {
        return WhatsappContact::query()->create([
            'phone' => '+255712345678',
            'opt_in_status' => WhatsappContact::OPT_IN_STATUS_OPTED_IN,
            'last_inbound_at' => $lastInboundAt,
        ]);
    }

    private function sms(): Sms {
        $sms = new Sms(['receiver' => '+255712345678', 'body' => 'Your token is 1234', 'status' => Sms::STATUS_STORED, 'sender_id' => 1]);
        $sms->trigger()->associate(Person::factory()->create());
        $sms->save();

        return $sms;
    }
}

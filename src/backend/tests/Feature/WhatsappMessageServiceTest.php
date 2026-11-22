<?php

namespace Tests\Feature;

use App\Models\MpmPlugin;
use App\Models\Person\Person;
use App\Models\Sms;
use App\Models\SmsAttempt;
use App\Plugins\WhatsappMessaging\Exceptions\WhatsappApiException;
use App\Plugins\WhatsappMessaging\Exceptions\WhatsappTemplateNotConfiguredException;
use App\Plugins\WhatsappMessaging\Models\WhatsappContact;
use App\Plugins\WhatsappMessaging\Models\WhatsappCredential;
use App\Plugins\WhatsappMessaging\Models\WhatsappTemplate;
use App\Plugins\WhatsappMessaging\Services\WhatsappMessageService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class WhatsappMessageServiceTest extends TestCase {
    use RefreshMultipleDatabases;

    private const string MESSAGES_URL = 'https://graph.facebook.com/v26.0/1234567890/messages';
    private const string WAMID = 'wamid.HBgMMjU1NzEyMzQ1Njc4FQIAERgSQ0E';
    private const array METER_RECEIPT_VALUES = ['name' => 'Amina', 'surname' => 'Juma', 'meter_serial' => '47000268748', 'token' => '1234-5678', 'energy' => '5.000'];

    private WhatsappCredential $credential;

    protected function setUp(): void {
        parent::setUp();

        $this->credential = new WhatsappCredential(['access_token' => 'test-access-token', 'phone_number_id' => '1234567890', 'business_account_id' => '9876543210', 'graph_api_version' => 'v26.0']);
    }

    public function testItSendsTheSmsTextAsFreeTextInsideTheWindow(): void {
        Http::fake([self::MESSAGES_URL => Http::response(['messages' => [['id' => self::WAMID]]])]);
        $sms = $this->sms();

        $this->send($sms, $this->contact(Carbon::now()->subHours(23)), 'TokenConfirmationMeter');

        Http::assertSent(fn (Request $request): bool => $request['type'] === 'text' && $request['text']['body'] === 'Your token is 1234');
        $this->assertDatabaseHas('sms_attempts', [
            'sms_id' => $sms->id,
            'gateway_id' => MpmPlugin::WHATSAPP_MESSAGING, 'external_id' => self::WAMID,
            'message_type' => SmsAttempt::MESSAGE_TYPE_TEXT,
            'template_name' => null,
            'status' => SmsAttempt::STATUS_ACCEPTED,
        ], 'tenant');
    }

    public function testItSendsTheNotificationsTemplateFromWhatsappManagerOutsideTheWindow(): void {
        $this->template('Hello {{name}} {{surname}}, meter {{meter_serial}} got token {{token}} for {{energy}} kWh.');
        Http::fake([self::MESSAGES_URL => Http::response(['messages' => [['id' => self::WAMID]]])]);
        $sms = $this->sms();

        $this->send($sms, $this->contact(Carbon::now()->subMinutes(23 * 60 + 55)), 'TokenConfirmationMeter');

        Http::assertSent(fn (Request $request): bool => $request['type'] === 'template'
            && $request['template']['name'] === 'token_confirmation_meter'
            && $request['template']['language'] === ['code' => 'en']
            && array_column($request['template']['components'][0]['parameters'], 'text', 'parameter_name') === self::METER_RECEIPT_VALUES);
        $this->assertDatabaseHas('sms_attempts', [
            'sms_id' => $sms->id,
            'message_type' => SmsAttempt::MESSAGE_TYPE_TEMPLATE,
            'template_name' => 'token_confirmation_meter',
            'status' => SmsAttempt::STATUS_ACCEPTED,
        ], 'tenant');
    }

    public function testItLogsAndRethrowsAMessageThatHasNoTemplate(): void {
        Http::fake();
        $sms = $this->sms();

        try {
            $this->send($sms, $this->contact(null), null);
            $this->fail('A manual message is not an MPM notification, so it has no template.');
        } catch (WhatsappTemplateNotConfiguredException) {
            Http::assertNothingSent();
        }

        $message = SmsAttempt::query()->where('sms_id', $sms->id)->sole();
        $this->assertSame(SmsAttempt::STATUS_FAILED, $message->status);
        $this->assertSame(SmsAttempt::MESSAGE_TYPE_TEMPLATE, $message->message_type);
        $this->assertNotNull($message->failed_at);
    }

    public function testATemplateWaitingForApprovalIsNotSentAndTheLogSaysWhy(): void {
        $this->template('Your token is {{token}}.', status: 'PENDING');
        Http::fake();
        $sms = $this->sms();

        try {
            $this->send($sms, $this->contact(null), 'TokenConfirmationMeter');
            $this->fail('A template Meta has not approved yet cannot be sent.');
        } catch (WhatsappTemplateNotConfiguredException) {
            Http::assertNothingSent();
        }

        $this->assertSame(
            'The WhatsApp template "token_confirmation_meter" is not approved (status: PENDING).',
            SmsAttempt::query()->where('sms_id', $sms->id)->sole()->error_message,
        );
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

        $message = SmsAttempt::query()->where('sms_id', $sms->id)->sole();
        $this->assertSame(SmsAttempt::STATUS_FAILED, $message->status);
        $this->assertSame(SmsAttempt::MESSAGE_TYPE_TEXT, $message->message_type);
        $this->assertSame(131030, $message->error_code);
        $this->assertNull($message->wamid);
    }

    private function template(string $body, string $status = WhatsappTemplate::STATUS_APPROVED): void {
        WhatsappTemplate::query()->create([
            'meta_template_id' => '594425479261596',
            'name' => 'token_confirmation_meter',
            'language' => 'en',
            'category' => 'UTILITY',
            'status' => $status,
            'parameter_format' => WhatsappTemplate::PARAMETER_FORMAT_NAMED,
            'components' => [['type' => 'BODY', 'text' => $body]],
        ]);
    }

    private function send(Sms $sms, WhatsappContact $contact, ?string $reference): void {
        resolve(WhatsappMessageService::class)->send($this->credential, $sms, $contact, $reference, self::METER_RECEIPT_VALUES, null);
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

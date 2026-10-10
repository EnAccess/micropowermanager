<?php

namespace Tests\Unit;

use App\Models\Person\Person;
use App\Models\Sms;
use App\Plugins\WhatsappMessaging\Exceptions\WhatsappMessageNotSentException;
use App\Plugins\WhatsappMessaging\Models\WhatsappCredential;
use App\Plugins\WhatsappMessaging\Models\WhatsappMessage;
use App\Plugins\WhatsappMessaging\Services\WhatsappOutboundMessage;
use App\Plugins\WhatsappMessaging\WhatsappMessagingGateway;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class WhatsappMessagingGatewayTest extends TestCase {
    use RefreshMultipleDatabases;

    private const string MESSAGES_URL = 'https://graph.facebook.com/v26.0/1234567890/messages';
    private const string WAMID = 'wamid.HBgMMjU1NzEyMzQ1Njc4FQIAERgSQ0E';

    protected function setUp(): void {
        parent::setUp();

        WhatsappCredential::query()->create([
            'access_token' => Crypt::encryptString('test-access-token'),
            'app_secret' => Crypt::encryptString('test-app-secret'),
            'webhook_verify_token' => Crypt::encryptString('test-verify-token'),
            'phone_number_id' => '1234567890',
        ]);
    }

    public function testAcceptedSendRecordsTheWamid(): void {
        Http::fake([self::MESSAGES_URL => Http::response(['messages' => [['id' => self::WAMID]]])]);
        $sms = $this->sms();

        resolve(WhatsappMessagingGateway::class)->sendSms(
            new WhatsappOutboundMessage('+255712345678', 'Payment received', 'payment_receipt', 'en', ['Amina']),
            $sms,
        );

        $this->assertDatabaseHas('whatsapp_messages', [
            'sms_id' => $sms->id,
            'wamid' => self::WAMID,
            'message_type' => WhatsappMessage::MESSAGE_TYPE_TEMPLATE,
            'template_name' => 'payment_receipt',
            'template_language' => 'en',
            'status' => WhatsappMessage::STATUS_ACCEPTED,
        ], 'tenant');
    }

    public function testFailedSendRecordsTheMetaErrorAndRethrows(): void {
        Http::fake([self::MESSAGES_URL => Http::response([
            'error' => ['message' => 'Recipient not allowed', 'code' => 131030, 'error_data' => ['details' => 'Recipient phone number not in allowed list']],
        ], 400)]);
        $sms = $this->sms();

        try {
            resolve('WhatsappMessagingGateway')->sendSms(new WhatsappOutboundMessage('+255712345678', 'Payment received'), $sms);
            $this->fail('The gateway should rethrow the Meta error so SmsProcessor can mark the sms failed.');
        } catch (WhatsappMessageNotSentException $exception) {
            $this->assertSame(131030, $exception->metaErrorCode);
        }

        $message = WhatsappMessage::query()->where('sms_id', $sms->id)->firstOrFail();
        $this->assertSame(WhatsappMessage::STATUS_FAILED, $message->status);
        $this->assertSame(WhatsappMessage::MESSAGE_TYPE_TEXT, $message->message_type);
        $this->assertSame(131030, $message->error_code);
        $this->assertStringContainsString('not in allowed list', (string) $message->error_message);
        $this->assertNotNull($message->failed_at);
        $this->assertNull($message->wamid);
    }

    private function sms(): Sms {
        $sms = new Sms([
            'receiver' => '+255712345678',
            'body' => 'Payment received',
            'status' => Sms::STATUS_STORED,
            'sender_id' => 1,
        ]);
        $sms->trigger()->associate(Person::factory()->create());
        $sms->save();

        return $sms;
    }
}

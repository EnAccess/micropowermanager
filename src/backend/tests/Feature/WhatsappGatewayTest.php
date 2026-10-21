<?php

namespace Tests\Feature;

use App\Models\MpmPlugin;
use App\Models\Person\Person;
use App\Models\Sms;
use App\Plugins\AfricasTalking\AfricasTalkingGateway;
use App\Plugins\Whatsapp\Exceptions\MessageNotSentException;
use App\Plugins\Whatsapp\Models\WhatsappCredential;
use App\Plugins\Whatsapp\Models\WhatsappMessage;
use App\Plugins\Whatsapp\WhatsappGateway;
use App\Services\SmsGatewayResolverService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Mockery\MockInterface;
use Tests\TestCase;

class WhatsappGatewayTest extends TestCase {
    private function createCredentials(bool $fallback = true): void {
        WhatsappCredential::query()->create([
            'phone_number_id' => '123456789',
            'access_token' => Crypt::encryptString('test-token'),
            'template_language' => 'en',
            'receipt_template' => 'mpm_payment_receipt',
            'reminder_template' => 'mpm_low_balance',
            'fallback_to_sms' => $fallback,
        ]);
    }

    private function createSms(?string $triggerType = null): Sms {
        return Sms::query()->create([
            'receiver' => '+250700000001',
            'body' => 'Your token is 1234',
            'direction' => Sms::DIRECTION_OUTGOING,
            'status' => Sms::STATUS_STORED,
            'trigger_type' => $triggerType,
        ]);
    }

    private function fakeMetaSuccess(): void {
        Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.OK1']]], 200)]);
    }

    public function testSuccessfulSendStoresTheMessageIdAndSendsTheReceiptTemplate(): void {
        $this->createCredentials();
        $this->fakeMetaSuccess();
        $sms = $this->createSms();

        resolve(WhatsappGateway::class)->sendSms('Your token is 1234', '+250 700 000 001', $sms);

        $row = WhatsappMessage::query()->where('sms_id', $sms->id)->firstOrFail();
        $this->assertSame('sent', $row->status);
        $this->assertSame('wamid.OK1', $row->message_id);
        $this->assertSame('mpm_payment_receipt', $row->template_name);

        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/123456789/messages')
            && $request->hasHeader('Authorization', 'Bearer test-token')
            && $request['to'] === '250700000001'
            && $request['type'] === 'template'
            && $request['template']['name'] === 'mpm_payment_receipt'
            && $request['template']['language']['code'] === 'en'
            && $request['template']['components'][0]['parameters'][1]['text'] === 'Your token is 1234');
    }

    public function testNewlinesAreRemovedFromTemplateParameters(): void {
        $this->createCredentials();
        $this->fakeMetaSuccess();

        resolve(WhatsappGateway::class)->sendSms("Line one\n\nLine\ttwo", '+250700000001', $this->createSms());

        Http::assertSent(function (Request $request): bool {
            $text = $request['template']['components'][0]['parameters'][1]['text'];

            return $text === 'Line one Line two';
        });
    }

    public function testApplianceRateMessagesUseTheReminderTemplate(): void {
        $this->createCredentials();
        $this->fakeMetaSuccess();

        resolve(WhatsappGateway::class)->sendSms('Payment due', '+250700000001', $this->createSms('appliance_rate'));

        Http::assertSent(fn (Request $request) => $request['template']['name'] === 'mpm_low_balance');
    }

    public function testMetaErrorIsLoggedAsFailedAndThrowsWhenThereIsNoFallback(): void {
        $this->createCredentials();
        Http::fake(['*' => Http::response(['error' => ['code' => 190, 'message' => 'Invalid token']], 401)]);
        $this->mock(SmsGatewayResolverService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getFallbackGatewayName')->andReturn(null);
        });
        $sms = $this->createSms();

        try {
            resolve(WhatsappGateway::class)->sendSms('hello', '+250700000001', $sms);
            $this->fail('Expected MessageNotSentException');
        } catch (MessageNotSentException $e) {
            $this->assertStringContainsString('190', $e->getMessage());
        }

        $row = WhatsappMessage::query()->where('sms_id', $sms->id)->firstOrFail();
        $this->assertSame('failed', $row->status);
        $this->assertNull($row->message_id);
    }

    public function testMissingCredentialsFailWithoutCallingMeta(): void {
        Http::fake();
        $this->mock(SmsGatewayResolverService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getFallbackGatewayName')->andReturn(null);
        });

        $this->expectException(MessageNotSentException::class);

        try {
            resolve(WhatsappGateway::class)->sendSms('hello', '+250700000001', $this->createSms());
        } finally {
            Http::assertNothingSent();
        }
    }

    public function testFailedWhatsappSendFallsBackToTheSmsGateway(): void {
        $this->createCredentials();
        Http::fake(['*' => Http::response(['error' => ['code' => 131030, 'message' => 'Not allowed']], 400)]);
        $sms = $this->createSms();

        $smsGateway = \Mockery::mock(AfricasTalkingGateway::class);
        $smsGateway->shouldReceive('sendSms')->once();

        $this->mock(SmsGatewayResolverService::class, function (MockInterface $mock) use ($smsGateway, $sms) {
            $mock->shouldReceive('getFallbackGatewayName')->andReturn(SmsGatewayResolverService::AFRICAS_TALKING_GATEWAY);
            $mock->shouldReceive('resolveGatewayAndArgs')->andReturn([
                'gateway' => $smsGateway,
                'args' => ['hello', '+250700000001', $sms],
                'gatewayId' => MpmPlugin::AFRICAS_TALKING,
            ]);
        });

        resolve(WhatsappGateway::class)->sendSms('hello', '+250700000001', $sms);

        $row = WhatsappMessage::query()->where('sms_id', $sms->id)->firstOrFail();
        $this->assertSame('fallback_sms', $row->status);
    }

    public function testFallbackIsSkippedWhenDisabledInSettings(): void {
        $this->createCredentials(fallback: false);
        Http::fake(['*' => Http::response(['error' => ['code' => 131030, 'message' => 'Not allowed']], 400)]);
        $this->mock(SmsGatewayResolverService::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('resolveGatewayAndArgs');
            $mock->shouldReceive('getFallbackGatewayName')->andReturn(SmsGatewayResolverService::AFRICAS_TALKING_GATEWAY);
        });

        $this->expectException(MessageNotSentException::class);

        resolve(WhatsappGateway::class)->sendSms('hello', '+250700000001', $this->createSms());
    }

    public function testTemplateGreetsTheCustomerByFirstName(): void {
        $this->createCredentials();
        $this->fakeMetaSuccess();

        $person = Person::query()->create([
            'name' => 'Alice',
            'surname' => 'Smith',
            'type' => 'customer',
            'is_customer' => 1,
        ]);
        $person->addresses()->create(['phone' => '+250700000001', 'is_primary' => 1]);

        resolve(WhatsappGateway::class)->sendSms('Your token is 1234', '+250700000001', $this->createSms());

        Http::assertSent(fn (Request $request) => $request['template']['components'][0]['parameters'][0]['text'] === 'Alice');
    }

    public function testUnknownNumbersAreGreetedAsCustomer(): void {
        $this->createCredentials();
        $this->fakeMetaSuccess();

        resolve(WhatsappGateway::class)->sendSms('Your token is 1234', '+250700000001', $this->createSms());

        Http::assertSent(fn (Request $request) => $request['template']['components'][0]['parameters'][0]['text'] === 'customer');
    }
}

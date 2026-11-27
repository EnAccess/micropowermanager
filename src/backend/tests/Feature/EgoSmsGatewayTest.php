<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\SmsProcessor;
use App\Models\MainSettings;
use App\Models\Sms;
use App\Plugins\EgoSmsGateway\Exceptions\MessageNotSentException;
use App\Plugins\EgoSmsGateway\Models\EgoSmsCredential;
use App\Plugins\EgoSmsGateway\Models\EgoSmsMessage;
use App\Plugins\EgoSmsGateway\Providers\EgoSmsGatewayServiceProvider;
use App\Plugins\EgoSmsGateway\Services\EgoSmsCredentialService;
use App\Services\SmsService;
use App\Sms\Senders\SmsConfigs;
use App\Sms\SmsTypes;
use Database\Factories\UserFactory;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class EgoSmsGatewayTest extends TestCase {
    use RefreshMultipleDatabases;

    private const string RECEIVER = '+256712345678';
    private const string MESSAGE = 'Your token is 1234';
    private const array CREDENTIALS = [
        'username' => 'operator',
        'password' => 'secret',
        'sender_id' => 'OPERATOR',
    ];

    protected function setUp(): void {
        parent::setUp();

        // AbstractJob resolves the tenant it belongs to from the first user on record.
        UserFactory::new()->create(['company_id' => $this->companyId]);
        MainSettings::query()->firstOrFail()->update(['sms_gateway_plugin_slug' => EgoSmsGatewayServiceProvider::SLUG]);
    }

    public function testAnSmsIsSentThroughEgoSmsAndMarkedAsSent(): void {
        $this->saveCredentials();
        Http::fake(['www.egosms.co/*' => Http::response(['Status' => 'OK', 'Cost' => '35', 'MsgFollowUpUniqueCode' => 'ApiJsonSubmit-1234'])]);

        $this->dispatchedSmsProcessor()->handle();

        Http::assertSent(function (Request $request): bool {
            $message = $request['msgdata'][0];

            return $request->url() === 'https://www.egosms.co/api/v1/json/'
                && $request['method'] === 'SendSms'
                && $request['userdata'] === ['username' => 'operator', 'password' => 'secret']
                && $message['number'] === '256712345678'
                && $message['message'] === self::MESSAGE
                && $message['senderid'] === 'OPERATOR';
        });
        $sms = Sms::query()->where('receiver', self::RECEIVER)->firstOrFail();
        $this->assertSame(Sms::STATUS_SENT, $sms->status);
        $this->assertSame(EgoSmsGatewayServiceProvider::SLUG, $sms->sms_gateway_plugin_slug);
        $this->assertSame('ApiJsonSubmit-1234', EgoSmsMessage::query()->where('sms_id', $sms->id)->firstOrFail()->follow_up_code);
    }

    public function testARejectedSmsIsMarkedAsFailedWithTheReasonEgoSmsGave(): void {
        $this->saveCredentials();
        Http::fake(['www.egosms.co/*' => Http::response(['Status' => 'Failed', 'Message' => 'Wrong Username Or Password'])]);

        $this->assertSmsFailsWith('EgoSMS message sending failed: Wrong Username Or Password');
    }

    public function testAnSmsFailsWhenEgoSmsCannotBeReached(): void {
        $this->saveCredentials();
        Http::fake(['www.egosms.co/*' => fn () => throw new ConnectionException('Connection timed out')]);

        $this->assertSmsFailsWith('Could not reach EgoSMS: Connection timed out');
    }

    public function testAnSmsFailsWhenEgoSmsAnswersWithSomethingElseThanItsApiReply(): void {
        $this->saveCredentials();
        Http::fake(['www.egosms.co/*' => Http::response('Bad Gateway', 502)]);

        $this->assertSmsFailsWith('EgoSMS message sending failed: unexpected response (HTTP 502)');
    }

    public function testAnSmsFailsWithoutCallingEgoSmsWhenCredentialsAreMissing(): void {
        Http::fake();

        $this->assertSmsFailsWith('EgoSMS credentials are not configured.');
        Http::assertNothingSent();
    }

    public function testCredentialsAreStoredWithAnEncryptedPassword(): void {
        $response = $this->actingAs(UserFactory::new()->create())
            ->putJson('/api/ego-sms-gateway/credential', self::CREDENTIALS);

        $response->assertOk();
        $response->assertJsonPath('data.username', 'operator');
        $response->assertJsonPath('data.sender_id', 'OPERATOR');
        $storedPassword = EgoSmsCredential::query()->firstOrFail()->password;
        $this->assertNotSame('secret', $storedPassword);
        $this->assertSame('secret', $this->app->make(EgoSmsCredentialService::class)->getCredentials()?->password);
    }

    public function testCredentialsWithoutASenderIdAreRejected(): void {
        $response = $this->actingAs(UserFactory::new()->create())
            ->putJson('/api/ego-sms-gateway/credential', ['username' => 'operator', 'password' => 'secret']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('sender_id');
    }

    private function assertSmsFailsWith(string $reason): void {
        $job = $this->dispatchedSmsProcessor();

        try {
            $job->handle();
            $this->fail('The gateway failure should propagate so the queue can retry it.');
        } catch (MessageNotSentException $exception) {
            $job->failed($exception);
        }

        $this->assertDatabaseHas('sms', [
            'receiver' => self::RECEIVER,
            'status' => Sms::STATUS_FAILED,
            'error_message' => $reason,
        ], 'tenant');
    }

    private function saveCredentials(): void {
        $this->app->make(EgoSmsCredentialService::class)->updateCredentials(self::CREDENTIALS);
    }

    private function dispatchedSmsProcessor(): SmsProcessor {
        Queue::fake();

        app()->make(SmsService::class)->sendSms(
            ['message' => self::MESSAGE, 'phone' => self::RECEIVER],
            SmsTypes::MANUAL_SMS,
            SmsConfigs::class
        );

        $job = Queue::pushed(SmsProcessor::class)->first();

        if (!$job instanceof SmsProcessor) {
            $this->fail('Sending an SMS should queue an SmsProcessor.');
        }

        return $job;
    }
}

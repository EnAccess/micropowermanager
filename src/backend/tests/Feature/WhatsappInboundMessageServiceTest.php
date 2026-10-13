<?php

namespace Tests\Feature;

use App\Plugins\WhatsappMessaging\Exceptions\WhatsappMessageNotSentException;
use App\Plugins\WhatsappMessaging\Models\WhatsappConsentLog;
use App\Plugins\WhatsappMessaging\Models\WhatsappContact;
use App\Plugins\WhatsappMessaging\Models\WhatsappCredential;
use App\Plugins\WhatsappMessaging\Services\WhatsappInboundMessageService;
use Database\Factories\Address\AddressFactory;
use Database\Factories\Person\PersonFactory;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WhatsappInboundMessageServiceTest extends TestCase {
    private const string SENDER = '255712345678';
    private const string PHONE_NUMBER_ID = '106540352242922';
    private const string MESSAGES_URL = 'https://graph.facebook.com/v26.0/'.self::PHONE_NUMBER_ID.'/messages';

    private WhatsappInboundMessageService $service;
    private PromiseInterface $metaResponse;

    protected function setUp(): void {
        parent::setUp();

        WhatsappCredential::query()->create([
            'access_token' => Crypt::encryptString('test-access-token'),
            'app_secret' => Crypt::encryptString('test-app-secret'),
            'webhook_verify_token' => Crypt::encryptString('test-verify-token'),
            'phone_number_id' => self::PHONE_NUMBER_ID,
            'business_account_id' => '123456789',
            'graph_api_version' => 'v26.0',
        ]);
        // Laravel uses the first matching fake, so tests change this response instead of registering a second fake.
        $this->metaResponse = Http::response(['messages' => [['id' => 'wamid.reply']]]);
        Http::fake([self::MESSAGES_URL => fn () => $this->metaResponse]);

        $this->service = resolve(WhatsappInboundMessageService::class);
    }

    /**
     * @return array<string, array{string, string|null}>
     */
    public static function keywordProvider(): array {
        return [
            'upper case opt-in' => ['JOIN', WhatsappConsentLog::ACTION_OPT_IN],
            'lower case opt-in' => ['join', WhatsappConsentLog::ACTION_OPT_IN],
            'mixed case opt-in' => ['Yes', WhatsappConsentLog::ACTION_OPT_IN],
            'surrounding spaces opt-in' => ['  start  ', WhatsappConsentLog::ACTION_OPT_IN],
            'surrounding newlines opt-in' => ["\nJOIN\n", WhatsappConsentLog::ACTION_OPT_IN],
            'lower case opt-out' => ['stop', WhatsappConsentLog::ACTION_OPT_OUT],
            'surrounding spaces opt-out' => [' Unsubscribe ', WhatsappConsentLog::ACTION_OPT_OUT],
            'sentence containing opt-out keyword' => ["I don't want to STOP", null],
            'sentence containing opt-in keyword' => ['Yes please call me', null],
            'unrelated text' => ['Hello', null],
            'empty text' => ['', null],
        ];
    }

    #[DataProvider('keywordProvider')]
    public function testMatchesWholeNormalisedMessageOnly(string $body, ?string $expectedAction): void {
        $this->assertSame($expectedAction, $this->service->matchKeyword($body));
    }

    public function testJoinOptsInLogsKeywordSourceAndSendsConfirmation(): void {
        $this->service->handle($this->textMessage('join'));

        $contact = $this->findContact();
        $this->assertSame(WhatsappContact::OPT_IN_STATUS_OPTED_IN, $contact->opt_in_status);
        $this->assertNotNull($contact->opted_in_at);
        $this->assertSame(self::SENDER, $contact->wa_id);
        $this->assertReplySent('Reply STOP to unsubscribe');

        $log = $contact->consentLogs()->sole();
        $this->assertSame(WhatsappConsentLog::ACTION_OPT_IN, $log->action);
        $this->assertSame(WhatsappConsentLog::SOURCE_KEYWORD, $log->source);
        $this->assertNull($log->user_id);
        $this->assertSame('join', $log->raw_message);
    }

    public function testStopOptsOutLogsAndSendsConfirmation(): void {
        $this->service->handle($this->textMessage('JOIN'));
        $this->service->handle($this->textMessage('STOP', '1760000100'));

        $contact = $this->findContact();
        $this->assertSame(WhatsappContact::OPT_IN_STATUS_OPTED_OUT, $contact->opt_in_status);
        $this->assertNotNull($contact->opted_out_at);
        Http::assertSentCount(2);
        $this->assertReplySent('Reply JOIN to subscribe again.');
        $this->assertSame(
            [WhatsappConsentLog::ACTION_OPT_IN, WhatsappConsentLog::ACTION_OPT_OUT],
            $contact->consentLogs()->orderBy('id')->pluck('action')->all(),
        );
    }

    public function testResentWebhookDoesNotCreateSecondLogRowOrReply(): void {
        $message = $this->textMessage('JOIN');

        $this->service->handle($message);
        $this->service->handle($message);

        $this->assertSame(1, $this->findContact()->consentLogs()->count());
        Http::assertSentCount(1);
    }

    public function testUnknownTextFromNonSubscriberGetsOptInButtonsOnce(): void {
        $this->service->handle($this->textMessage('Hello, my meter is not working'));
        $this->service->handle($this->textMessage('Are you there?', '1760000100'));

        $contact = $this->findContact();
        $this->assertSame(WhatsappContact::OPT_IN_STATUS_OPTED_OUT, $contact->opt_in_status);
        $this->assertNotNull($contact->opt_in_prompted_at);
        $this->assertSame(0, $contact->consentLogs()->count());
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->url() === self::MESSAGES_URL
            && $request['to'] === self::SENDER
            && $request['type'] === 'interactive'
            && $request['interactive']['action']['buttons'] === [
                ['type' => 'reply', 'reply' => ['id' => WhatsappInboundMessageService::OPT_IN_BUTTON_ID, 'title' => 'Yes, subscribe']],
                ['type' => 'reply', 'reply' => ['id' => WhatsappInboundMessageService::OPT_OUT_BUTTON_ID, 'title' => 'No, thanks']],
            ]);
    }

    public function testOptInButtonsAreSentAgainAfterThirtyDays(): void {
        $this->service->handle($this->textMessage('Hello'));
        $this->findContact()->update(['opt_in_prompted_at' => now()->subDays(31)]);

        $this->service->handle($this->textMessage('Hello again', '1760000100'));

        Http::assertSentCount(2);
        $this->assertTrue($this->findContact()->opt_in_prompted_at?->isAfter(now()->subMinute()));
    }

    public function testSubscriberIsNotPromptedToOptIn(): void {
        $this->service->handle($this->textMessage('JOIN'));

        $this->service->handle($this->textMessage('Hello, my meter is not working', '1760000100'));

        // A subscriber's free text is answered by the self-service bot, never with the opt-in buttons.
        Http::assertNotSent(fn (Request $request): bool => ($request['type'] ?? null) === 'interactive');
        $this->assertNull($this->findContact()->opt_in_prompted_at);
    }

    public function testFailedOptInPromptIsRetriedOnTheNextMessage(): void {
        $this->metaResponse = Http::response(['error' => ['code' => 131000, 'message' => 'Something went wrong']], 500);

        try {
            $this->service->handle($this->textMessage('Hello'));
            $this->fail('Expected the failed prompt to throw.');
        } catch (WhatsappMessageNotSentException) {
            $this->assertNull($this->findContact()->opt_in_prompted_at);
        }

        $this->metaResponse = Http::response(['messages' => [['id' => 'wamid.reply']]]);
        $this->service->handle($this->textMessage('Hello again', '1760000100'));

        Http::assertSentCount(2);
        $this->assertNotNull($this->findContact()->opt_in_prompted_at);
    }

    public function testTappingYesSubscribeOptsInLikeTheJoinKeyword(): void {
        $this->service->handle($this->buttonReply(WhatsappInboundMessageService::OPT_IN_BUTTON_ID, 'Yes, subscribe'));

        $contact = $this->findContact();
        $this->assertSame(WhatsappContact::OPT_IN_STATUS_OPTED_IN, $contact->opt_in_status);
        $this->assertReplySent('Reply STOP to unsubscribe');

        $log = $contact->consentLogs()->sole();
        $this->assertSame(WhatsappConsentLog::ACTION_OPT_IN, $log->action);
        $this->assertSame(WhatsappConsentLog::SOURCE_KEYWORD, $log->source);
        $this->assertSame('Yes, subscribe', $log->raw_message);
    }

    public function testTappingNoThanksAsNonSubscriberChangesNothing(): void {
        $this->service->handle($this->buttonReply(WhatsappInboundMessageService::OPT_OUT_BUTTON_ID, 'No, thanks'));

        $contact = $this->findContact();
        $this->assertSame(WhatsappContact::OPT_IN_STATUS_OPTED_OUT, $contact->opt_in_status);
        $this->assertSame(0, $contact->consentLogs()->count());
        Http::assertNothingSent();
    }

    public function testNonTextMessageOnlyUpdatesLastInbound(): void {
        $this->service->handle([
            'from' => self::SENDER,
            'id' => 'wamid.image',
            'timestamp' => '1760000000',
            'type' => 'image',
        ]);

        $contact = $this->findContact();
        $this->assertSame(1760000000, $contact->last_inbound_at?->getTimestamp());
        $this->assertSame(0, $contact->consentLogs()->count());
        Http::assertNothingSent();
    }

    public function testFailedReplyKeepsTheConsentChange(): void {
        $this->metaResponse = Http::response(['error' => ['code' => 131030, 'message' => 'Recipient not allowed']], 400);

        try {
            $this->service->handle($this->textMessage('JOIN'));
            $this->fail('Expected the failed reply to throw.');
        } catch (WhatsappMessageNotSentException $exception) {
            $this->assertSame(131030, $exception->metaErrorCode);
        }

        $contact = $this->findContact();
        $this->assertSame(WhatsappContact::OPT_IN_STATUS_OPTED_IN, $contact->opt_in_status);
        $this->assertSame(1, $contact->consentLogs()->count());
    }

    public function testOlderResentMessageDoesNotMoveLastInboundBackwards(): void {
        $this->service->handle($this->textMessage('Hello', '1760000100'));
        $this->service->handle($this->textMessage('Hello', '1760000000'));

        $this->assertSame(1760000100, $this->findContact()->last_inbound_at?->getTimestamp());
    }

    public function testLinksContactToCustomerByPhone(): void {
        $person = PersonFactory::new()->create();
        $address = AddressFactory::new()->make(['phone' => '+'.self::SENDER]);
        $address->owner()->associate($person);
        $address->save();

        $this->service->handle($this->textMessage('JOIN'));

        $this->assertSame($person->id, $this->findContact()->person_id);
    }

    private function assertReplySent(string $expectedText): void {
        Http::assertSent(fn (Request $request): bool => $request->url() === self::MESSAGES_URL
            && $request['to'] === self::SENDER
            && $request['type'] === 'text'
            && str_contains($request['text']['body'], $expectedText));
    }

    /**
     * @return array{from: string, id: string, timestamp: string, type: string, text: array{body: string}}
     */
    private function textMessage(string $body, string $timestamp = '1760000000'): array {
        return [
            'from' => self::SENDER,
            'id' => 'wamid.'.$timestamp,
            'timestamp' => $timestamp,
            'type' => 'text',
            'text' => ['body' => $body],
        ];
    }

    /**
     * @return array{from: string, id: string, timestamp: string, type: string, interactive: array{type: string, button_reply: array{id: string, title: string}}}
     */
    private function buttonReply(string $buttonId, string $title): array {
        return [
            'from' => self::SENDER,
            'id' => 'wamid.button',
            'timestamp' => '1760000000',
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button_reply',
                'button_reply' => ['id' => $buttonId, 'title' => $title],
            ],
        ];
    }

    private function findContact(): WhatsappContact {
        return WhatsappContact::query()->where('phone', '+'.self::SENDER)->sole();
    }
}

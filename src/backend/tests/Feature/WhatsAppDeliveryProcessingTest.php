<?php

namespace Tests\Feature;

use App\Plugins\WhatsApp\Services\WhatsAppDeliveryService;
use Illuminate\Database\Connection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\WhatsAppTestCase;

class WhatsAppDeliveryProcessingTest extends WhatsAppTestCase {
    private WhatsAppDeliveryService $service;

    protected function setUp(): void {
        parent::setUp();
        Carbon::setTestNow(Carbon::createFromTimestampUTC(1700001000));
        $this->service = resolve(WhatsAppDeliveryService::class);
    }

    protected function tearDown(): void {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function testProviderStatusesUpdateDeliveryWithoutResending(): void {
        $this->message();
        foreach (['sent', 'delivered', 'read'] as $offset => $status) {
            $this->service->accept(1, [$this->event($status, 1700000000 + $offset)]);
            $this->assertSame($status, $this->storedMessage()->status);
        }

        $message = $this->storedMessage();
        $this->assertTimestamp(1700000000, $message->provider_sent_at);
        $this->assertTimestamp(1700000001, $message->delivered_at);
        $this->assertTimestamp(1700000002, $message->read_at);
        $this->assertTimestamp(1700000002, $message->provider_status_at);
        $this->assertNotNull($message->delivery_status_updated_at);
        $this->assertSame(3, DB::connection('tenant')->table('whatsapp_delivery_events')->where('state', 'processed')->count());
        Http::assertNothingSent();
    }

    public function testDuplicateEventsCollapseWithoutTrustingProviderErrorText(): void {
        $this->message();
        $event = $this->event('failed', 1700000000);
        $event['errors'] = [['code' => 131026, 'title' => 'sensitive title', 'message' => 'sensitive provider message']];
        $this->service->accept(1, [$event, $event]);
        $event['errors'][0]['message'] = 'different arbitrary text';
        $this->service->accept(1, [$event]);

        $message = $this->storedMessage();
        $this->assertSame('failed', $message->status);
        $this->assertSame('131026', (string) $message->provider_error_code);
        $this->assertNotEmpty($message->error_message);
        $this->assertStringNotContainsString('sensitive', $message->error_message);
        $this->assertStringNotContainsString('arbitrary', $message->error_message);
        $this->assertSame(1, DB::connection('tenant')->table('whatsapp_delivery_events')->count());
        Http::assertNothingSent();
    }

    public function testReadIsTerminalAndDeliveryOverridesFailureRegardlessOfProviderTime(): void {
        $this->message();
        $this->service->accept(1, [$this->event('failed', 1700000030)]);
        $this->service->accept(1, [$this->event('delivered', 1700000010)]);
        $this->assertSame('delivered', $this->storedMessage()->status);
        $this->service->accept(1, [$this->event('failed', 1700000040)]);
        $this->assertSame('delivered', $this->storedMessage()->status);
        $this->service->accept(1, [$this->event('read', 1700000005)]);
        $this->service->accept(1, [$this->event('sent', 1700000050), $this->event('failed', 1700000060)]);

        $this->assertSame('read', $this->storedMessage()->status);
        $this->assertTimestamp(1700000005, $this->storedMessage()->provider_status_at);
    }

    public function testFailureAndRecoveryRespectProviderTimestamps(): void {
        $this->message();
        $this->service->accept(1, [$this->event('sent', 1700000020)]);
        $this->service->accept(1, [$this->event('failed', 1700000010)]);
        $this->assertSame('sent', $this->storedMessage()->status);
        $this->service->accept(1, [$this->event('failed', 1700000020)]);
        $this->assertSame('failed', $this->storedMessage()->status);
        $this->service->accept(1, [$this->event('sent', 1700000020)]);
        $this->assertSame('failed', $this->storedMessage()->status);
        $this->service->accept(1, [$this->event('sent', 1700000030)]);

        $this->assertSame('sent', $this->storedMessage()->status);
        $this->assertTimestamp(1700000030, $this->storedMessage()->provider_status_at);
    }

    public function testEarlierTimestampsAreRetainedWithoutRegressingReadStatus(): void {
        $this->message();
        $this->service->accept(1, [$this->event('sent', 1700000030), $this->event('delivered', 1700000040), $this->event('read', 1700000050)]);
        $this->service->accept(1, [$this->event('read', 1700000020), $this->event('delivered', 1700000010), $this->event('sent', 1700000000)]);

        $message = $this->storedMessage();
        $this->assertSame('read', $message->status);
        $this->assertTimestamp(1700000000, $message->provider_sent_at);
        $this->assertTimestamp(1700000010, $message->delivered_at);
        $this->assertTimestamp(1700000020, $message->read_at);
    }

    public function testUnknownMessageIsDurableAndReconcilesAfterPersistence(): void {
        $this->service->accept(1, [$this->event('delivered', 1700000000)]);
        $event = DB::connection('tenant')->table('whatsapp_delivery_events')->first();
        $this->assertSame('pending', $event->state);
        $this->assertSame(1, (int) $event->attempts);
        $this->assertNotNull($event->next_attempt_at);
        $this->service->reconcile(1);
        $this->assertSame(1, (int) DB::connection('tenant')->table('whatsapp_delivery_events')->first()->attempts);

        $this->message();
        Carbon::setTestNow(Carbon::parse($event->next_attempt_at)->addSecond());
        $counts = $this->service->reconcile(1);

        $this->assertSame('delivered', $this->storedMessage()->status);
        $this->assertSame('processed', DB::connection('tenant')->table('whatsapp_delivery_events')->first()->state);
        $this->assertSame(0, $counts['pending']);
        Http::assertNothingSent();
    }

    public function testUnknownMessageExhaustionPreservesInboxEvidence(): void {
        $this->service->accept(1, [$this->event('delivered', 1700000000)]);
        for ($attempt = 1; $attempt < 8; ++$attempt) {
            $event = DB::connection('tenant')->table('whatsapp_delivery_events')->first();
            Carbon::setTestNow(Carbon::parse($event->next_attempt_at)->addSecond());
            $this->service->reconcile(1);
        }

        $event = DB::connection('tenant')->table('whatsapp_delivery_events')->first();
        $this->assertSame('exhausted', $event->state);
        $this->assertSame(8, (int) $event->attempts);
        $this->assertSame(1, DB::connection('tenant')->table('whatsapp_delivery_events')->count());
        $counts = $this->service->reconcile(1);
        $this->assertSame(0, $counts['pending']);
        $this->assertSame(1, $counts['exhausted']);
        Http::assertNothingSent();
    }

    public function testScheduledReconciliationUsesExistingCompanyAndHonorsEnvironmentGate(): void {
        $this->service->accept(1, [$this->event('delivered', 1700000000)]);
        $event = DB::connection('tenant')->table('whatsapp_delivery_events')->first();
        $this->message();
        Carbon::setTestNow(Carbon::parse($event->next_attempt_at)->addSecond());
        $this->artisan('whatsapp:reconcile-delivery', ['--company' => '1'])->assertSuccessful();
        $this->assertSame('delivered', $this->storedMessage()->status);
        $this->artisan('whatsapp:reconcile-delivery', ['--company' => '99'])->assertExitCode(2);
        $this->app->instance('env', 'production');
        $this->artisan('whatsapp:reconcile-delivery', ['--company' => '1'])->assertFailed();
    }

    public function testFailuresRetainProviderTimeAndNotificationTypeWithSafeDiagnostics(): void {
        $this->message();
        $this->service->accept(1, [$this->event('failed', 1700000000) + ['errors' => [['code' => 131026, 'message' => 'Bearer sensitive-token']]]]);
        $message = $this->storedMessage();
        $this->assertTimestamp(1700000000, $message->failed_at);
        $this->assertSame('131026', $message->provider_error_code);
        $event = DB::connection('tenant')->table('whatsapp_delivery_events')->first();
        $this->assertSame('test', $event->notification_type);
        $this->assertStringNotContainsString('sensitive-token', json_encode($event));
        $this->assertStringNotContainsString('sensitive-token', $message->error_message);
        $this->service->accept(1, [$this->event('read', 1700000001)]);
        $this->assertNull($this->storedMessage()->failed_at);
        $this->assertNull($this->storedMessage()->error_message);
        $this->assertSame('131026', DB::connection('tenant')->table('whatsapp_delivery_events')->where('status', 'failed')->value('error_code'));
    }

    public function testProviderTimestampDoesNotReplaceLocalSendTime(): void {
        $this->message();
        DB::connection('tenant')->table('whatsapp_messages')->update(['sent_at' => '2023-11-14 22:30:00']);
        $this->service->accept(1, [$this->event('sent', 1700000000)]);
        $this->assertSame('2023-11-14 22:30:00', $this->storedMessage()->sent_at);
        $this->assertTimestamp(1700000000, $this->storedMessage()->provider_sent_at);
    }

    public function testDuplicatePendingReceiptsDoNotResetRetryBudget(): void {
        $event = $this->event('delivered', 1700000000);
        $this->service->accept(1, [$event]);
        $before = DB::connection('tenant')->table('whatsapp_delivery_events')->first();
        $this->service->accept(1, [$event, $event]);
        $this->assertEquals($before, DB::connection('tenant')->table('whatsapp_delivery_events')->first());
    }

    public function testUnsupportedStatusCannotChangeMessage(): void {
        $this->message();
        $this->service->accept(1, [$this->event('unsupported-future-state', 1700000000)]);

        $this->assertSame('sent', $this->storedMessage()->status);
        $this->assertSame('unsupported', DB::connection('tenant')->table('whatsapp_delivery_events')->first()->state);
    }

    public function testCompanyResolutionIsIndependentOfActiveTenantAndProviderIdCollision(): void {
        $this->message();
        $otherTenant = $this->otherTenant();
        try {
            $this->message($otherTenant);
            $this->service->accept(2, [$this->event('read', 1700000000)]);

            $this->assertSame('sent', $this->storedMessage()->status);
            $this->assertSame('read', $otherTenant->table('whatsapp_messages')->first()->status);
            $this->assertSame(0, DB::connection('tenant')->table('whatsapp_delivery_events')->count());
            $this->assertSame(1, $otherTenant->table('whatsapp_delivery_events')->count());
            $this->assertSame($this->tenantPaths[1], DB::connection('tenant')->getDatabaseName());
        } finally {
            $otherTenant->disconnect();
        }
        Http::assertNothingSent();
    }

    private function message(?Connection $connection = null): void {
        ($connection ?? DB::connection('tenant'))->table('whatsapp_messages')->insert([
            'message_id' => 'wamid.example',
            'recipient_phone' => '+250788123456',
            'notification_type' => 'test',
            'status' => 'sent',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function storedMessage(): object {
        return DB::connection('tenant')->table('whatsapp_messages')->first();
    }

    private function event(string $status, int $timestamp): array {
        return ['id' => 'wamid.example', 'status' => $status, 'timestamp' => (string) $timestamp];
    }

    private function assertTimestamp(int $expected, ?string $actual): void {
        $this->assertNotNull($actual);
        $this->assertSame($expected, Carbon::parse($actual, 'UTC')->getTimestamp());
    }

    private function otherTenant(): Connection {
        return resolve('db.factory')->make(['driver' => 'sqlite', 'database' => $this->tenantPaths[2], 'prefix' => '']);
    }
}

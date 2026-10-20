<?php

namespace Tests\Feature;

use App\Models\User;
use App\Plugins\WhatsApp\Services\WhatsAppApiClient;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Tests\WhatsAppTestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class WhatsAppEndToEndTest extends WhatsAppTestCase {
    public function testConfigurationSendSignedBatchAndHistoryWithMockNotificationDispatch(): void {
        Schema::connection('tenant')->create('users', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('company_id');
            $table->string('name');
            $table->string('email');
            $table->string('password');
        });
        DB::connection('tenant')->table('users')->insert([
            'id' => 1, 'company_id' => 1, 'name' => 'Integration user', 'email' => 'integration@example.com', 'password' => 'unused',
        ]);
        $user = new User(['company_id' => 1]);
        $user->setAttribute('id', 1);
        $headers = ['Authorization' => 'Bearer '.JWTAuth::fromUser($user)];
        $this->resetAuthentication();
        $response = $this->putJson('/api/whatsapp/credential', [
            'phone_number_id' => 'phone-1', 'whatsapp_business_account_id' => 'account-1',
            'api_access_token' => 'test-access-token', 'webhook_verify_token' => 'verify-1',
            'meta_app_secret' => 'secret-1', 'is_enabled' => true,
        ], $headers)->assertSuccessful()->assertJsonPath('data.has_meta_app_secret', true);
        $this->assertStringNotContainsString('test-access-token', $response->getContent());
        $stored = DB::connection('tenant')->table('whatsapp_credentials')->first();
        $this->assertSame('secret-1', Crypt::decryptString($stored->meta_app_secret));
        $this->assertNotSame('test-access-token', $stored->api_access_token);

        $this->resetAuthentication();
        $this->get('/api/whatsapp/webhook/1?hub.mode=subscribe&hub.verify_token=verify-1&hub.challenge=12345')
            ->assertOk()->assertContent('12345');
        Http::fake(['graph.facebook.com/*' => Http::sequence()
            ->push(['messages' => [['id' => 'wamid.test']]])
            ->push(['messages' => [['id' => 'wamid.payment']]])
            ->push(['messages' => [['id' => 'wamid.token']]])
            ->push(['messages' => [['id' => 'wamid.balance']]])
            ->push(['messages' => [['id' => 'wamid.late']]])]);
        $this->resetAuthentication();
        $this->postJson('/api/whatsapp/test-message', ['recipient_phone' => '15555550100', 'message' => 'Test fixture'], $headers)
            ->assertOk()->assertJsonPath('data.message_id', 'wamid.test');

        // Task 3 triggers are absent here; these type strings are illustrative dispatch fixtures.
        foreach (['payment', 'token', 'balance'] as $type) {
            resolve(WhatsAppApiClient::class)->sendTextMessage('15555550100', 'Mock notification', $type);
        }
        $statuses = [];
        foreach (['test' => 'sent', 'payment' => 'delivered', 'token' => 'read', 'balance' => 'failed'] as $type => $status) {
            $event = ['id' => 'wamid.'.$type, 'status' => $status, 'timestamp' => '1700000000'];
            if ($status === 'failed') {
                $event['errors'] = [['code' => 131026, 'title' => 'sensitive-provider-canary', 'error_data' => ['details' => 'sensitive-provider-canary']]];
            }
            $statuses[] = $event;
        }
        $this->postWebhook($statuses)->assertOk();
        $this->postWebhook($statuses)->assertOk();
        $this->assertSame(4, DB::connection('tenant')->table('whatsapp_delivery_events')->count());

        $this->postWebhook([['id' => 'wamid.late', 'status' => 'read', 'timestamp' => '1700000001']])->assertOk();
        $this->assertSame('pending', DB::connection('tenant')->table('whatsapp_delivery_events')->where('message_id', 'wamid.late')->value('state'));
        resolve(WhatsAppApiClient::class)->sendTextMessage('15555550100', 'Delayed persistence fixture', 'token');
        $this->travel(61)->seconds();
        $this->artisan('whatsapp:reconcile-delivery', ['--company' => '1'])->assertSuccessful();
        $this->resetAuthentication();
        $history = $this->getJson('/api/whatsapp/messages', $headers)->assertOk()->assertJsonPath('meta.total', 5);
        $messages = collect($history->json('data'))->keyBy('message_id');
        foreach (['test' => 'sent', 'payment' => 'delivered', 'token' => 'read', 'balance' => 'failed', 'late' => 'read'] as $type => $status) {
            $this->assertSame($status, $messages['wamid.'.$type]['status']);
        }
        $this->assertSame('payment', $messages['wamid.payment']['notification_type']);
        $this->assertSame('131026', (string) $messages['wamid.balance']['provider_error_code']);
        $this->assertNotNull($messages['wamid.balance']['failed_at']);
        $this->assertNotNull($messages['wamid.late']['read_at']);
        $this->assertStringNotContainsString('sensitive-provider-canary', $history->getContent());
        $this->assertStringNotContainsString('test-access-token', $history->getContent());
        Http::assertSentCount(5);
    }

    public function testReconciliationScheduleIsRegisteredAndGated(): void {
        $events = collect(resolve(Schedule::class)->events())->filter(fn ($event): bool => str_contains($event->command ?? '', 'whatsapp:reconcile-delivery'));
        $this->assertCount(1, $events);
        $event = $events->first();
        $this->assertSame('* * * * *', $event->expression);
        $this->assertTrue($event->withoutOverlapping);
        $this->assertTrue($event->filtersPass($this->app));
        config(['whatsapp.webhooks_enabled' => false]);
        $this->assertFalse($event->filtersPass($this->app));
        config(['whatsapp.webhooks_enabled' => true]);
        $this->app->instance('env', 'production');
        $this->assertFalse($event->filtersPass($this->app));
    }

    private function resetAuthentication(): void {
        // HTTP tests reuse JWT's singleton, unlike fresh application requests.
        auth()->forgetGuards();
        resolve('tymon.jwt')->unsetToken();
    }

    private function postWebhook(array $statuses): TestResponse {
        $this->resetAuthentication();
        $payload = $this->payload();
        $payload['entry'][0]['changes'][0]['value']['statuses'] = $statuses;
        $body = json_encode($payload, JSON_THROW_ON_ERROR);

        return $this->call('POST', '/api/whatsapp/webhook/1', server: [
            'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $body, 'secret-1'),
        ], content: $body);
    }
}

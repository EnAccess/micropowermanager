<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DatabaseProxyManagerService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Tests\WhatsAppTestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class WhatsAppMessageHistoryTest extends WhatsAppTestCase {
    protected function setUp(): void {
        parent::setUp();
        foreach ($this->tenantPaths as $companyId => $path) {
            $connection = resolve('db.factory')->make(['driver' => 'sqlite', 'database' => $path, 'prefix' => '']);
            try {
                $connection->getSchemaBuilder()->create('users', function (Blueprint $table): void {
                    $table->increments('id');
                    $table->unsignedInteger('company_id');
                    $table->string('name');
                    $table->string('email');
                    $table->string('password');
                });
                $connection->table('users')->insert(['id' => 1, 'company_id' => $companyId, 'name' => 'History user', 'email' => 'history@example.com', 'password' => 'unused']);
            } finally {
                $connection->disconnect();
            }
        }
        $manager = \Mockery::mock(DatabaseProxyManagerService::class);
        $manager->shouldReceive('runForCompany')->andReturnUsing(function (int $companyId, callable $operation): mixed {
            $previous = config('database.connections.tenant');
            config(['database.connections.tenant' => ['driver' => 'sqlite', 'database' => $this->tenantPaths[$companyId], 'prefix' => '']]);
            DB::purge('tenant');
            try {
                return $operation();
            } finally {
                config(['database.connections.tenant' => $previous]);
                DB::purge('tenant');
            }
        });
        $this->app->instance(DatabaseProxyManagerService::class, $manager);
    }

    public function testHistoryRequiresValidJwtAndExistingUser(): void {
        $this->getJson('/api/whatsapp/messages')->assertUnauthorized();
        auth()->forgetGuards();
        $this->getJson('/api/whatsapp/messages', ['Authorization' => 'Bearer invalid-token'])->assertUnauthorized();
        $this->history(headers: $this->headersFor(1, 99))->assertUnauthorized();
        $headers = $this->headersFor(1);
        DB::connection('tenant')->table('users')->where('id', 1)->delete();
        $this->history(headers: $headers)->assertUnauthorized();
    }

    public function testUserCompanyMustMatchAuthenticatedTenant(): void {
        DB::connection('tenant')->table('users')->where('id', 1)->update(['company_id' => 2]);
        $this->history()->assertForbidden();
    }

    public function testRealTenantFilesAreIsolatedWithTheSameLocalUserAndMessageIds(): void {
        $this->insertMessage(1, ['message_id' => 'tenant-one']);
        $this->insertMessage(2, ['message_id' => 'tenant-two']);
        $this->history(headers: $this->headersFor(1))->assertOk()->assertJsonPath('data.0.message_id', 'tenant-one')->assertJsonCount(1, 'data');
        $this->history(headers: $this->headersFor(2))->assertOk()->assertJsonPath('data.0.message_id', 'tenant-two')->assertJsonCount(1, 'data');
    }

    public function testPaginationHasStableOrderingAndPreservesNullTimestamps(): void {
        for ($index = 1; $index <= 3; ++$index) {
            $this->insertMessage(1, ['message_id' => 'message-'.$index]);
        }
        $first = $this->history('?per_page=2')->assertOk()->assertJsonPath('meta.total', 3)->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('data.0.message_id', 'message-3')->assertJsonPath('data.1.message_id', 'message-2');
        foreach (['sent_at', 'provider_sent_at', 'delivered_at', 'read_at', 'failed_at'] as $field) {
            $first->assertJsonPath('data.0.'.$field, null);
        }
        $first->assertJsonPath('data.0.created_at', '2026-10-14T12:00:00.000000Z')->assertJsonStructure(['data', 'links', 'meta']);
        $this->history('?per_page=2&page=2')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.message_id', 'message-1');
        $this->history('?page=10000')->assertOk()->assertJsonCount(0, 'data');
    }

    public function testCreationTimestampPrecedesIdAndAllDeliveryTimesSerializeAsUtc(): void {
        $this->insertMessage(1, [
            'message_id' => 'newer', 'status' => 'failed', 'created_at' => '2026-10-14 12:00:00',
            'sent_at' => '2026-10-14 12:00:01', 'provider_sent_at' => '2026-10-14 12:00:02',
            'delivered_at' => '2026-10-14 12:00:03', 'read_at' => '2026-10-14 12:00:04',
            'failed_at' => '2026-10-14 12:00:05',
        ]);
        $this->insertMessage(1, ['message_id' => 'older-higher-id', 'created_at' => '2026-10-13 12:00:00']);
        $this->insertMessage(1, ['created_at' => null]);
        $response = $this->history()->assertOk()->assertJsonPath('data.0.message_id', 'newer')
            ->assertJsonPath('data.1.message_id', 'older-higher-id')->assertJsonPath('data.2.created_at', null)
            ->assertJsonPath('data.2.message_id', null);
        foreach (['sent_at' => 1, 'provider_sent_at' => 2, 'delivered_at' => 3, 'read_at' => 4, 'failed_at' => 5] as $field => $second) {
            $response->assertJsonPath('data.0.'.$field, '2026-10-14T12:00:0'.$second.'.000000Z');
        }
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        foreach (['customer', 'reference', 'body', 'database_name'] as $field) {
            $this->assertArrayNotHasKey($field, $response->json('data.0'));
        }
    }

    public function testAllSupportedStatusFiltersAndPaginationMaximumAreAccepted(): void {
        foreach (['queued', 'sending', 'sent', 'delivered', 'read', 'failed'] as $status) {
            $this->insertMessage(1, ['status' => $status]);
            $this->history('?status='.$status.'&per_page=100')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.status', $status);
        }
    }

    public function testFiltersApplyTogetherAndDatesIncludeTheWholeUtcDay(): void {
        $this->insertMessage(1, ['message_id' => 'before', 'status' => 'read', 'notification_type' => 'payment', 'created_at' => '2026-10-13 23:59:59']);
        $this->insertMessage(1, ['message_id' => 'start', 'status' => 'read', 'notification_type' => 'payment', 'created_at' => '2026-10-14 00:00:00']);
        $this->insertMessage(1, ['message_id' => 'end', 'status' => 'read', 'notification_type' => 'payment', 'created_at' => '2026-10-14 23:59:59']);
        $this->insertMessage(1, ['message_id' => 'after', 'status' => 'read', 'notification_type' => 'payment', 'created_at' => '2026-10-15 00:00:00']);
        $this->insertMessage(1, ['message_id' => 'wrong-status', 'status' => 'sent', 'notification_type' => 'payment']);
        $this->insertMessage(1, ['message_id' => 'wrong-type', 'status' => 'read', 'notification_type' => 'test']);
        $this->history('?status=read&notification_type=payment&date_from=2026-10-14&date_to=2026-10-14')
            ->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('data.0.message_id', 'end')->assertJsonPath('data.1.message_id', 'start');
        $this->history('?notification_type='.urlencode("payment' OR 1=1 --"))->assertOk()->assertJsonCount(0, 'data');
        $this->history('?date_to=2026-10-13')->assertOk()->assertJsonCount(1, 'data');
        $this->history('?date_from=2026-10-15')->assertOk()->assertJsonCount(1, 'data');
    }

    public function testInvalidOrUnknownQueriesAreRejectedWithoutEchoingSecrets(): void {
        foreach ([
            'page=0', 'page=10001', 'page=1.5', 'page[]=1', 'per_page=0', 'per_page=101',
            'status=unknown', 'status=READ', 'notification_type='.str_repeat('a', 256),
            'date_from=2026-02-30', 'date_to=tomorrow', 'date_from=2026-10-15&date_to=2026-10-14',
            'api_access_token=history-secret-canary', 'company_id=2', 'companyId=2',
            'database_name=tenant-two', 'connection=tenant', 'tenant_id=2',
            'per_page=', 'notification_type[]=test', 'date_from[]=2026-10-14',
        ] as $query) {
            $response = $this->history('?'.$query)->assertUnprocessable();
            $this->assertStringNotContainsString('history-secret-canary', $response->getContent());
        }
    }

    public function testFailureFieldsAreSanitizedAndBodiesNeverAppear(): void {
        $this->insertMessage(1, ['message_id' => 'failed-valid', 'status' => 'failed', 'provider_error_code' => '131026', 'error_message' => 'secret-provider-error', 'body' => 'secret-body']);
        $this->insertMessage(1, ['message_id' => 'failed-invalid', 'status' => 'failed', 'provider_error_code' => 'secret-code']);
        $this->insertMessage(1, ['message_id' => 'read', 'status' => 'read', 'provider_error_code' => '131026', 'error_message' => 'secret-provider-error']);
        $response = $this->history()->assertOk()->assertJsonPath('data.0.error_message', null)
            ->assertJsonPath('data.1.provider_error_code', null)->assertJsonPath('data.1.error_message', 'Message delivery failed.')
            ->assertJsonPath('data.2.provider_error_code', '131026')->assertJsonPath('data.2.error_message', 'Message delivery failed.');
        foreach (['secret-provider-error', 'secret-body', 'secret-code', 'api_access_token', 'webhook_verify_token', 'meta_app_secret'] as $secret) {
            $this->assertStringNotContainsString($secret, $response->getContent());
        }
        $this->assertArrayNotHasKey('body', $response->json('data.2'));
    }

    public function testEmptyHistoryNeedsNoCredentialsAndPerformsNoWritesOrSensitiveReads(): void {
        $queries = [];
        DB::listen(function ($event) use (&$queries): void {
            $queries[] = strtolower($event->sql);
        });
        $this->history()->assertOk()->assertJsonCount(0, 'data')->assertJsonPath('meta.total', 0)->assertJsonPath('meta.per_page', 25);
        $this->insertMessage(1, ['message_id' => 'no-credentials-required']);
        $queries = [];
        $this->history()->assertOk()->assertJsonCount(1, 'data');
        $messageQueries = array_filter($queries, fn (string $query): bool => str_contains($query, 'whatsapp_messages'));
        $this->assertCount(2, $messageQueries);
        foreach ($queries as $query) {
            $this->assertDoesNotMatchRegularExpression('/\b(insert|update|delete|replace)\b/', $query);
            $this->assertStringNotContainsString('whatsapp_credentials', $query);
            if (str_contains($query, 'whatsapp_messages')) {
                $this->assertStringNotContainsString('"body"', $query);
                $this->assertStringNotContainsString('"error_message"', $query);
            }
        }
    }

    private function history(string $query = '', ?array $headers = null) {
        $headers ??= $this->headersFor(1);
        auth()->forgetGuards();
        // Real requests boot a fresh application; HTTP tests reuse JWT's cached token.
        resolve('tymon.jwt')->unsetToken();

        return $this->getJson('/api/whatsapp/messages'.$query, $headers);
    }

    private function headersFor(int $companyId, int $userId = 1): array {
        $user = new User(['company_id' => $companyId]);
        $user->setAttribute('id', $userId);
        $token = JWTAuth::fromUser($user);
        auth()->forgetGuards();

        return ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'];
    }

    private function insertMessage(int $companyId, array $attributes): void {
        $connection = resolve('db.factory')->make(['driver' => 'sqlite', 'database' => $this->tenantPaths[$companyId], 'prefix' => '']);
        try {
            $connection->table('whatsapp_messages')->insert(array_replace([
                'recipient_phone' => '250788123456', 'notification_type' => 'test', 'status' => 'sent',
                'created_at' => '2026-10-14 12:00:00', 'updated_at' => '2026-10-14 12:00:00',
            ], $attributes));
        } finally {
            $connection->disconnect();
        }
    }
}

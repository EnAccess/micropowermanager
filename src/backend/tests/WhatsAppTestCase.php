<?php

namespace Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

abstract class WhatsAppTestCase extends TestCase {
    /** @var array<int, string> */
    protected array $tenantPaths = [];

    protected function setUp(): void {
        parent::setUp();
        Http::preventStrayRequests();
        config(['cache.default' => 'array', 'whatsapp.webhooks_enabled' => true]);
        foreach (['micro_power_manager', 'tenant'] as $name) {
            DB::purge($name);
            config(["database.connections.{$name}" => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        }
        Schema::connection('micro_power_manager')->create('companies', function (Blueprint $table): void {
            $table->increments('id');
        });
        Schema::connection('micro_power_manager')->create('company_databases', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('company_id');
            $table->string('database_name');
        });
        for ($companyId = 1; $companyId <= 2; ++$companyId) {
            $path = tempnam(sys_get_temp_dir(), 'mpm-whatsapp-test-');
            $this->tenantPaths[$companyId] = $path;
            DB::connection('micro_power_manager')->table('companies')->insert(['id' => $companyId]);
            DB::connection('micro_power_manager')->table('company_databases')->insert(['company_id' => $companyId, 'database_name' => $path]);
            config(['database.connections.tenant' => ['driver' => 'sqlite', 'database' => $path, 'prefix' => '']]);
            DB::purge('tenant');
            (require database_path('migrations/tenant/2026_10_10_000002_create_whatsapp_tables.php'))->up();
            (require database_path('migrations/tenant/2026_10_14_000001_add_meta_app_secret_to_whatsapp_credentials.php'))->up();
            (require database_path('migrations/tenant/2026_10_14_000002_add_whatsapp_delivery_tracking.php'))->up();
        }
        config(['database.connections.tenant.database' => $this->tenantPaths[1]]);
        DB::purge('tenant');
    }

    protected function tearDown(): void {
        DB::purge('tenant');
        DB::purge('micro_power_manager');
        parent::tearDown();
        foreach ($this->tenantPaths as $path) {
            unlink($path);
        }
    }

    protected function seedWebhookCredentials(int $companyId = 1, array $overrides = []): void {
        $connection = resolve('db.factory')->make(['driver' => 'sqlite', 'database' => $this->tenantPaths[$companyId], 'prefix' => '']);
        try {
            $connection->table('whatsapp_credentials')->insert(array_replace([
                'id' => 1,
                'is_enabled' => true,
                'phone_number_id' => 'phone-'.$companyId,
                'whatsapp_business_account_id' => 'account-'.$companyId,
                'webhook_verify_token' => Crypt::encryptString('verify-'.$companyId),
                'meta_app_secret' => Crypt::encryptString('secret-'.$companyId),
            ], $overrides));
        } finally {
            $connection->disconnect();
        }
    }

    protected function payload(int $companyId = 1): array {
        return ['object' => 'whatsapp_business_account', 'entry' => [[
            'id' => 'account-'.$companyId,
            'changes' => [['field' => 'messages', 'value' => [
                'messaging_product' => 'whatsapp', 'metadata' => ['phone_number_id' => 'phone-'.$companyId],
                'statuses' => [['id' => 'wamid.example', 'status' => 'delivered', 'timestamp' => '1700000000']],
            ]]],
        ]]];
    }
}

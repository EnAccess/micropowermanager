<?php

namespace Tests\Feature;

use App\Models\CompanyDatabase;
use App\Plugins\WhatsApp\Services\WhatsAppTenantConnectionService;
use App\Plugins\WhatsApp\Services\WhatsAppWebhookCredentialReader;
use Illuminate\Database\Connection;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\WhatsAppTestCase;

class WhatsAppWebhookTenantIsolationTest extends WhatsAppTestCase {
    public function testSeparateTenantDatabasesReturnOnlyTheirOwnAuthenticationContext(): void {
        $this->seedWebhookCredentials(1);
        $this->seedWebhookCredentials(2);
        $configuration = config('database.connections.tenant');
        $active = DB::connection('tenant');
        $reader = resolve(WhatsAppWebhookCredentialReader::class);
        $this->assertSame(['secret' => 'secret-1', 'account' => 'account-1', 'phone' => 'phone-1'], $reader->read(1, false));
        $this->assertSame(['secret' => 'secret-2', 'account' => 'account-2', 'phone' => 'phone-2'], $reader->read(2, false));
        $this->assertSame('verify-1', $reader->read(1, true)['secret']);
        $this->assertSame($configuration, config('database.connections.tenant'));
        $this->assertSame($active, DB::connection('tenant'));
        $this->assertSame('account-1', $active->table('whatsapp_credentials')->value('whatsapp_business_account_id'));
    }

    public function testMissingRowsAreNeverInitialized(): void {
        foreach ([1, 2, 99] as $companyId) {
            $this->assertRejected(resolve(WhatsAppWebhookCredentialReader::class), $companyId);
        }
        $this->assertSame(0, DB::connection('tenant')->table('whatsapp_credentials')->count());
    }

    public function testDisabledIncompleteAndCorruptCredentialsFailClosed(): void {
        foreach ([
            ['is_enabled' => false], ['meta_app_secret' => null], ['meta_app_secret' => 'plaintext-is-not-ciphertext'],
            ['phone_number_id' => null], ['whatsapp_business_account_id' => null],
        ] as $overrides) {
            DB::connection('tenant')->table('whatsapp_credentials')->delete();
            $this->seedWebhookCredentials(1, $overrides);
            $this->assertRejected(resolve(WhatsAppWebhookCredentialReader::class), 1);
        }
    }

    public function testCandidateConnectionIsDisconnectedOnSuccessAndDecryptionFailure(): void {
        $this->seedWebhookCredentials();
        foreach ([false, true] as $corrupt) {
            if ($corrupt) {
                DB::connection('tenant')->table('whatsapp_credentials')->update(['meta_app_secret' => 'corrupt']);
            }
            $factory = \Mockery::mock(ConnectionFactory::class);
            $connection = resolve('db.factory')->make(['driver' => 'sqlite', 'database' => $this->tenantPaths[1], 'prefix' => '']);
            $factory->shouldReceive('make')->once()->with(\Mockery::on(fn (array $configuration): bool => $configuration['database'] === $this->tenantPaths[1]))->andReturn($connection);
            $reader = new WhatsAppWebhookCredentialReader(new WhatsAppTenantConnectionService(new CompanyDatabase(), $factory));
            if ($corrupt) {
                $this->assertRejected($reader, 1);
            } else {
                $reader->read(1, false);
            }
            $this->assertNull($connection->getRawPdo());
        }
    }

    public function testCandidateConnectionIsDisconnectedOnQueryFailure(): void {
        $factory = \Mockery::mock(ConnectionFactory::class);
        $connection = \Mockery::mock(Connection::class);
        $factory->shouldReceive('make')->once()->andReturn($connection);
        $connection->shouldReceive('table')->once()->with('whatsapp_credentials')->andThrow(new \RuntimeException('sensitive database detail'));
        $connection->shouldReceive('disconnect')->once();
        $this->assertRejected(new WhatsAppWebhookCredentialReader(new WhatsAppTenantConnectionService(new CompanyDatabase(), $factory)), 1);
    }

    public function testRegistryCannotResolveAnOrphanedCompany(): void {
        $this->seedWebhookCredentials();
        DB::connection('micro_power_manager')->table('companies')->where('id', 1)->delete();
        $factory = \Mockery::mock(ConnectionFactory::class);
        $factory->shouldNotReceive('make');
        $this->assertRejected(new WhatsAppWebhookCredentialReader(new WhatsAppTenantConnectionService(new CompanyDatabase(), $factory)), 1);
    }

    private function assertRejected(WhatsAppWebhookCredentialReader $reader, int $companyId): void {
        try {
            $reader->read($companyId, false);
            $this->fail('Expected rejected authentication context.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
            $this->assertSame('Webhook request rejected.', $exception->getMessage());
            $this->assertNull($exception->getPrevious());
        }
    }
}

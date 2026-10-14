<?php

namespace Tests\Feature;

use App\Plugins\WhatsApp\Services\WhatsAppDeliveryService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Sleep;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Process\Process;

class WhatsAppDeliveryMysqlTest extends TestCase {
    private string $scratchDatabase;
    private array $trustedConfiguration;

    protected function setUp(): void {
        parent::setUp();
        if (getenv('WHATSAPP_MYSQL_TESTS') !== '1') {
            $this->markTestSkipped('Set WHATSAPP_MYSQL_TESTS=1 to run isolated MySQL concurrency tests.');
        }
        $this->trustedConfiguration = config('database.connections.micro_power_manager');
        $this->assertSame('mysql', $this->trustedConfiguration['driver']);
        $this->assertSame('mysql_testing', $this->trustedConfiguration['host']);
        $this->assertSame('mpm_testing', $this->trustedConfiguration['database']);
        $this->assertEmpty($this->trustedConfiguration['unix_socket'] ?? '');
        foreach (['url', 'read', 'write'] as $key) {
            $this->assertEmpty($this->trustedConfiguration[$key] ?? null);
        }
        config(['database.connections.whatsapp_test_admin' => $this->trustedConfiguration]);
        $administrator = DB::connection('whatsapp_test_admin');
        $this->assertSame('mpm_testing', $administrator->selectOne('SELECT DATABASE() AS database_name')->database_name);
        $this->scratchDatabase = 'mpm_whatsapp_m2_'.bin2hex(random_bytes(8));
        $administrator->statement('CREATE DATABASE `'.$this->scratchDatabase.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        fwrite(STDERR, "\nRetained WhatsApp MySQL scratch database: {$this->scratchDatabase}\n");
        $configuration = array_replace($this->trustedConfiguration, ['database' => $this->scratchDatabase]);
        foreach (['micro_power_manager', 'tenant'] as $connection) {
            config(['database.connections.'.$connection => $configuration]);
            DB::purge($connection);
            $this->assertSame($this->scratchDatabase, DB::connection($connection)->selectOne('SELECT DATABASE() AS database_name')->database_name);
        }
        Schema::connection('micro_power_manager')->create('companies', function (Blueprint $table): void {
            $table->increments('id');
        });
        Schema::connection('micro_power_manager')->create('company_databases', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('company_id');
            $table->string('database_name');
        });
        DB::connection('micro_power_manager')->table('companies')->insert(['id' => 1]);
        DB::connection('micro_power_manager')->table('company_databases')->insert(['company_id' => 1, 'database_name' => $this->scratchDatabase]);
        foreach ([
            '2026_10_10_000002_create_whatsapp_tables.php',
            '2026_10_14_000001_add_meta_app_secret_to_whatsapp_credentials.php',
            '2026_10_14_000002_add_whatsapp_delivery_tracking.php',
        ] as $migration) {
            (require database_path('migrations/tenant/'.$migration))->up();
        }
        foreach (['whatsapp_messages', 'whatsapp_delivery_events'] as $table) {
            $engine = DB::connection('tenant')->selectOne(
                'SELECT ENGINE AS engine FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
                [$this->scratchDatabase, $table]
            );
            $this->assertSame('InnoDB', $engine->engine);
        }
    }

    public function testConcurrentReadAndDeliveredCannotRegress(): void {
        $this->insertMessage('concurrent-order');
        $this->runConcurrent('concurrent-order', ['read', 'delivered']);
        $message = DB::connection('tenant')->table('whatsapp_messages')->first();
        $this->assertSame('read', $message->status);
        $this->assertNotNull($message->read_at);
        $this->assertNotNull($message->delivered_at);
        $this->assertSame(2, DB::connection('tenant')->table('whatsapp_delivery_events')->where('state', 'processed')->count());
    }

    public function testConcurrentIdenticalReceiptsUseOneInboxRow(): void {
        $this->insertMessage('concurrent-duplicate');
        $this->runConcurrent('concurrent-duplicate', ['read', 'read']);
        $events = DB::connection('tenant')->table('whatsapp_delivery_events')->get();
        $this->assertCount(1, $events);
        $this->assertSame('processed', $events[0]->state);
        $this->assertSame(1, (int) $events[0]->attempts);
        $this->assertSame('read', DB::connection('tenant')->table('whatsapp_messages')->value('status'));
    }

    public function testCorrelationIsCaseSensitiveAndAmbiguousIdsAreNotUpdated(): void {
        $this->insertMessage('CaseSensitive');
        $this->insertMessage('casesensitive');
        $service = resolve(WhatsAppDeliveryService::class);
        $service->accept(1, [$this->statusEvent('CaseSensitive', 'read')]);
        $messages = DB::connection('tenant')->table('whatsapp_messages')->orderBy('id')->get();
        $this->assertSame('read', $messages[0]->status);
        $this->assertSame('sent', $messages[1]->status);
        $this->insertMessage('ambiguous');
        $this->insertMessage('ambiguous');
        $service->accept(1, [$this->statusEvent('ambiguous', 'delivered')]);
        $this->assertSame(2, DB::connection('tenant')->table('whatsapp_messages')->where('message_id', 'ambiguous')->where('status', 'sent')->count());
        $this->assertSame('ambiguous_message_id', DB::connection('tenant')->table('whatsapp_delivery_events')->where('message_id', 'ambiguous')->value('last_error'));
    }

    public function testSqlFailurePreservesDurableInboxForRetry(): void {
        $this->insertMessage('wamid.fail');
        DB::connection('tenant')->unprepared("CREATE TRIGGER whatsapp_test_update_failure BEFORE UPDATE ON whatsapp_messages FOR EACH ROW BEGIN IF OLD.message_id = 'wamid.fail' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Isolated delivery test failure'; END IF; END");
        try {
            resolve(WhatsAppDeliveryService::class)->accept(1, [$this->statusEvent('wamid.fail', 'delivered')]);
            $this->fail('A SQL update failure must request a retry.');
        } catch (HttpException $exception) {
            $this->assertSame(503, $exception->getStatusCode());
        }
        $event = DB::connection('tenant')->table('whatsapp_delivery_events')->sole();
        $this->assertSame('pending', $event->state);
        $this->assertSame(1, (int) $event->attempts);
        $this->assertSame('processing_failed', $event->last_error);
        $this->assertNotNull($event->next_attempt_at);
        $this->assertSame('sent', DB::connection('tenant')->table('whatsapp_messages')->value('status'));
        DB::connection('tenant')->table('whatsapp_delivery_events')->update(['next_attempt_at' => now()->utc()->format('Y-m-d H:i:s')]);
        $this->assertTrue(resolve(WhatsAppDeliveryService::class)->reconcile(1)['processing_errors']);
        config(['whatsapp.webhooks_enabled' => true]);
        DB::connection('tenant')->table('whatsapp_delivery_events')->update(['next_attempt_at' => now()->utc()->format('Y-m-d H:i:s')]);
        $this->artisan('whatsapp:reconcile-delivery', ['--company' => '1'])->assertFailed();
        $this->insertMessage('wamid.finishfail');
        DB::connection('tenant')->unprepared("CREATE TRIGGER whatsapp_test_completion_failure BEFORE UPDATE ON whatsapp_delivery_events FOR EACH ROW BEGIN IF NEW.state = 'processed' AND OLD.message_id = 'wamid.finishfail' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Isolated completion test failure'; END IF; END");
        try {
            resolve(WhatsAppDeliveryService::class)->accept(1, [$this->statusEvent('wamid.finishfail', 'read')]);
            $this->fail('Inbox completion failure must roll the message update back.');
        } catch (HttpException $exception) {
            $this->assertSame(503, $exception->getStatusCode());
        }
        $this->assertSame('sent', DB::connection('tenant')->table('whatsapp_messages')->where('message_id', 'wamid.finishfail')->value('status'));
        $this->assertSame('pending', DB::connection('tenant')->table('whatsapp_delivery_events')->where('message_id', 'wamid.finishfail')->value('state'));
    }

    private function insertMessage(string $messageId): void {
        DB::connection('tenant')->table('whatsapp_messages')->insert([
            'message_id' => $messageId, 'recipient_phone' => '250788123456',
            'notification_type' => 'test', 'status' => 'sent',
        ]);
    }

    private function statusEvent(string $messageId, string $status): array {
        return ['id' => $messageId, 'status' => $status, 'timestamp' => '1791979200'];
    }

    private function runConcurrent(string $messageId, array $statuses): void {
        $connection = DB::connection('tenant');
        $workers = [];
        $markers = [];
        $connection->beginTransaction();
        try {
            $connection->table('whatsapp_messages')->where('message_id', $messageId)->lockForUpdate()->first();
            foreach ($statuses as $status) {
                $marker = sys_get_temp_dir().'/whatsapp-ready-'.bin2hex(random_bytes(12));
                $markers[] = $marker;
                $worker = new Process([
                    PHP_BINARY, base_path('tests/support/whatsapp_delivery_worker.php'),
                    $this->scratchDatabase, $messageId, $status, '1791979200', $marker,
                ], base_path(), [
                    'APP_ENV' => 'testing', 'DB_CONNECTION' => 'micro_power_manager',
                    'DB_HOST' => 'mysql_testing', 'DB_DATABASE' => 'mpm_testing',
                    'DB_PORT' => (string) $this->trustedConfiguration['port'],
                    'DB_USERNAME' => $this->trustedConfiguration['username'],
                    'DB_PASSWORD' => $this->trustedConfiguration['password'],
                    'DB_SOCKET' => '', 'WHATSAPP_MYSQL_TESTS' => '1',
                ]);
                $worker->setTimeout(20);
                $worker->start();
                $workers[] = $worker;
            }
            $deadline = microtime(true) + 10;
            do {
                $ready = count(array_filter($markers, is_file(...))) === 2
                    && DB::connection('micro_power_manager')->table('whatsapp_delivery_events')->count() >= 1;
                foreach ($workers as $worker) {
                    $this->assertTrue($worker->isRunning(), $worker->getErrorOutput().$worker->getOutput());
                }
                if ($ready) {
                    break;
                }
                Sleep::usleep(10000);
            } while (microtime(true) < $deadline);
            $this->assertTrue($ready, 'Both independent workers must reach the service while the parent holds the message lock.');
            Sleep::usleep(200000);
            foreach ($workers as $worker) {
                $this->assertTrue($worker->isRunning(), $worker->getErrorOutput().$worker->getOutput());
            }
            $connection->commit();
            foreach ($workers as $worker) {
                $this->assertSame(0, $worker->wait(), $worker->getErrorOutput().$worker->getOutput());
            }
        } finally {
            if ($connection->transactionLevel() > 0) {
                $connection->rollBack();
            }
            foreach ($workers as $worker) {
                if ($worker->isRunning()) {
                    $worker->stop();
                }
            }
        }
    }
}

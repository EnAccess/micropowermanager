<?php

use App\Plugins\WhatsApp\Services\WhatsAppDeliveryService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

if (getenv('WHATSAPP_MYSQL_TESTS') !== '1' || count($argv) !== 6
    || !preg_match('/^mpm_whatsapp_m2_[a-f0-9]{16}$/D', $argv[1])
    || !preg_match('/^[a-zA-Z0-9-]{1,100}$/D', $argv[2])
    || !in_array($argv[3], ['read', 'delivered'], true)
    || !preg_match('/^[0-9]{10}$/D', $argv[4])
    || !preg_match('~^'.preg_quote(sys_get_temp_dir(), '~').'/whatsapp-ready-[a-f0-9]{24}$~D', $argv[5])) {
    exit(2);
}

require dirname(__DIR__).'/bootstrap.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$configuration = config('database.connections.micro_power_manager');
if ($configuration['host'] !== 'mysql_testing' || $configuration['database'] !== 'mpm_testing'
    || !empty($configuration['unix_socket']) || !empty($configuration['url'])
    || !empty($configuration['read']) || !empty($configuration['write'])) {
    exit(3);
}
if (DB::connection('micro_power_manager')->selectOne('SELECT DATABASE() AS database_name')->database_name !== 'mpm_testing') {
    exit(4);
}
$configuration['database'] = $argv[1];
foreach (['micro_power_manager', 'tenant'] as $connection) {
    config(['database.connections.'.$connection => $configuration]);
    DB::purge($connection);
    if (DB::connection($connection)->selectOne('SELECT DATABASE() AS database_name')->database_name !== $argv[1]) {
        exit(5);
    }
}
file_put_contents($argv[5], 'ready', LOCK_EX);
resolve(WhatsAppDeliveryService::class)->accept(1, [['id' => $argv[2], 'status' => $argv[3], 'timestamp' => $argv[4]]]);

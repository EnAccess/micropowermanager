<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Tests\Support\TestDatabaseTarget;

require __DIR__.'/../vendor/autoload.php';

/*
| Running the test suite might be destructive.
| It spins tenant databases up and down as part of the testing procedure.
| Using this bootstrap file as a safeguard to make sure the test suite
| only ever touches the dedicated testing database.
|
| Abort otherwise to prevent it running against a development or live database.
*/
/** @var Application $app */
$app = require __DIR__.'/../bootstrap/app.php';
$app->bootstrapWith([
    LoadEnvironmentVariables::class,
    LoadConfiguration::class,
]);

$requiredDatabase = 'mpm_testing';

$connection = (string) config('database.default');
$target = config("database.connections.{$connection}", []);
$driver = (string) ($target['driver'] ?? '');
$database = (string) ($target['database'] ?? '');
$host = (string) ($target['host'] ?? '');
$port = (string) ($target['port'] ?? '');
$socket = (string) ($target['unix_socket'] ?? '');

if ($connection !== 'micro_power_manager'
    || !TestDatabaseTarget::isApproved($driver, $database, $host, $port, $socket)) {
    fwrite(STDERR, implode("\n", [
        '',
        str_repeat('=', 80),
        'ABORTING TEST RUN — the effective database target is not approved for tests.',
        '',
        sprintf('  connection : %s', $connection),
        sprintf('  database   : %s @ %s:%s', $database, $host, $port),
        '',
        sprintf('Use database "%s" at 127.0.0.1:53306 or mysql_testing:3306.', $requiredDatabase),
        'Set the approved DB_* values explicitly, then run tests from src/backend.',
        '',
        'The effective Laravel connection settings are checked; credentials are not displayed.',
        str_repeat('=', 80),
        '',
    ])."\n");

    exit(1);
}

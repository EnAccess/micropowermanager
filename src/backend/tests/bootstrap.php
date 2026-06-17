<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;

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
$database = (string) config("database.connections.{$connection}.database");

if ($database !== $requiredDatabase) {
    $host = (string) config("database.connections.{$connection}.host");
    $port = (string) config("database.connections.{$connection}.port");

    fwrite(STDERR, implode("\n", [
        '',
        str_repeat('=', 80),
        'ABORTING TEST RUN — not connected to a dedicated testing database.',
        '',
        sprintf('  connection : %s', $connection),
        sprintf('  database   : %s @ %s:%s', $database, $host, $port),
        '',
        sprintf('The database name must be exactly "%s". Run the suite from', $requiredDatabase),
        'the HOST so .env.testing is used:  cd src/backend && php artisan test',
        '',
        'Do NOT run it inside an application Docker container: its DB_* environment',
        'variables might point to an actual databases and override .env.testing.',
        str_repeat('=', 80),
        '',
    ])."\n");

    exit(1);
}

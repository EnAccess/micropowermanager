<?php

declare(strict_types=1);

namespace Tests\Support;

final class TestDatabaseTarget {
    public static function isApproved(
        string $driver,
        string $database,
        string $host,
        string $port,
        string $socket,
    ): bool {
        if ($driver !== 'mysql' || $database !== 'mpm_testing' || $socket !== '') {
            return false;
        }

        return ($host === '127.0.0.1' && $port === '53306')
            || ($host === 'mysql_testing' && $port === '3306');
    }
}

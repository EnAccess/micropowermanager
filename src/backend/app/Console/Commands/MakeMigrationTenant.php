<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MakeMigrationTenant extends Command {
    protected $signature = 'make:migration-tenant {migration-name}';
    protected $description = 'Create new migration file for tenant database(s)';

    public function handle(): int {
        $migrationName = $this->argument('migration-name');
        $timestamp = date('Y_m_d_His');

        $this->call('make:migration', [
            'name' => $migrationName,
            '--path' => '/database/migrations/tenant',
        ]);

        $path = database_path("migrations/tenant/{$timestamp}_{$migrationName}.php");

        if (is_file($path)) {
            $contents = file_get_contents($path);

            if ($contents !== false) {
                $updated = str_replace(
                    "Schema::connection('micropowermanager')",
                    "Schema::connection('tenant')",
                    $contents,
                    $count
                );

                if ($count > 0 && $updated !== $contents) {
                    file_put_contents($path, $updated);
                }
            }
        }

        return 0;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\Artisan;

class SeedDatabaseCommand extends AbstractSharedCommand
{

    protected $signature ='shard:db';
    function handle(): void
    {
        try {
            Artisan::call('db:seed');
        } catch (\Throwable $t) {
            $this->info("failed seeding ". $t->getMessage() );
        }
    }

    function runInCompanyScope(): void
    {
        // TODO: Implement runInCompanyScope() method.
    }
}

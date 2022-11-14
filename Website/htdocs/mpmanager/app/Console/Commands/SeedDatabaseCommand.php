<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\Artisan;

class SeedDatabaseCommand extends AbstractSharedCommand
{
    protected $signature = 'shard:db';
    public function handle(): void
    {
        try {
            Artisan::call('db:seed');
        } catch (\Throwable $t) {
            $this->info("failed seeding " . $t->getMessage());
        }
    }

}

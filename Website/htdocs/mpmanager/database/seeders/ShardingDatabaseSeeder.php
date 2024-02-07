<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ShardingDatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call(MpmPluginsSeeder::class);
        $this->call(ProtectedPagesSeeder::class);
        $this->call(UsageTypeSeeder::class);
    }
}

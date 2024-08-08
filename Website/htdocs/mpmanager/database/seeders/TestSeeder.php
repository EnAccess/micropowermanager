<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::connection('micro_power_manager')->table('mpm_plugins')->insert([
            [
                'name' => 'BulkRegistration',
                'description' => 'This plugin provides bulk registration of the company\'s existing records. NOTE: Please do not use this plugin to register your Spark & Stemaco meter records. These records will be synchronized automatically once you configure your credential settings for these plugins.',
                'tail_tag' => null,
                'installation_command' => 'bulk-registration:install',
            ],
        ]
        );
    }
}

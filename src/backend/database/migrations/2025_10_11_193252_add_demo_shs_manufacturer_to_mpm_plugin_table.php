<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        DB::table('mpm_plugins')->insert([
            [
                'id' => 22,
                'name' => 'DemoShsManufacturer',
                'description' => 'Demo manufacturer plugin for Solar Home Systems (SHS) that generates realistic tokens and transactions without requiring real manufacturer API integration. Perfect for testing and demonstration purposes.',
                'tail_tag' => 'DemoShsManufacturer',
                'installation_command' => 'demo-shs-manufacturer:install',
                'root_class' => 'DemoShsManufacturer',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        DB::table('mpm_plugins')
            ->where('id', 22)
            ->delete();
    }
};

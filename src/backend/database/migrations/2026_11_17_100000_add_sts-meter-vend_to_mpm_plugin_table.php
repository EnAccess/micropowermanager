<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        DB::table('mpm_plugins')->insert([
            [
                'id' => 33,
                'name' => 'StsMeterVend',
                'description' => 'Vends IEC 62055-41 STS tokens for registered STS meters through a self-hosted nxt-sts service.',
                'installation_command' => 'sts-meter-vend:install',
                'root_class' => 'StsMeterVend',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }

    public function down(): void {
        DB::table('mpm_plugins')->where('id', 33)->delete();
    }
};

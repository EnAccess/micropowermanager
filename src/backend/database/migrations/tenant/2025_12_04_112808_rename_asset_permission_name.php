<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        DB::table('permissions')
            ->where('name', 'assets')
            ->update(['name' => 'appliances']);
    }

    public function down(): void {
        DB::table('permissions')
            ->where('name', 'appliances')
            ->update(['name' => 'assets']);
    }
};

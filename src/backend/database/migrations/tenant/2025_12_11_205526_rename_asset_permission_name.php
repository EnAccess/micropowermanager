<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        DB::connection('tenant')->table('permissions')
            ->where('name', 'assets')
            ->update(['name' => 'appliances']);
    }

    public function down(): void {
        DB::connection('tenant')->table('permissions')
            ->where('name', 'appliances')
            ->update(['name' => 'assets']);
    }
};
